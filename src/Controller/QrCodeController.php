<?php

/*
 * UrlRewrite Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2021, terminal42 gmbh
 * @author     terminal42 <https://terminal42.ch>
 * @license    MIT
 */

namespace Terminal42\UrlRewriteBundle\Controller;

use Contao\Backend;
use Contao\BackendTemplate;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\File;
use Contao\Input;
use Contao\StringUtil;
use Contao\Widget;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Terminal42\UrlRewriteBundle\QrCodeGenerator;

class QrCodeController
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var QrCodeGenerator
     */
    private $qrCodeGenerator;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * QrCodeController constructor.
     */
    public function __construct(Connection $connection, QrCodeGenerator $qrCodeGenerator, RequestStack $requestStack)
    {
        $this->connection = $connection;
        $this->qrCodeGenerator = $qrCodeGenerator;
        $this->requestStack = $requestStack;
    }

    /**
     * Index view.
     */
    public function index(): Response
    {
        if (($request = $this->requestStack->getCurrentRequest()) === null
            || !($id = $request->query->getInt('id'))
            || ($rewriteData = $this->connection->fetchAssociative('SELECT * FROM tl_url_rewrite WHERE id=?', [$id])) === false
            || !$this->qrCodeGenerator->validate($rewriteData)
        ) {
            throw new PageNotFoundException();
        }

        $routeParameters = [
            'scheme' => $request->getScheme(),
            'host' => null,
        ];

        $template = new BackendTemplate('be_url_rewrite_qr_code');
        $template->backUrl = Backend::getReferer();

        // Add form to the template
        $this->addFormToTemplate($template, $request, $rewriteData, $routeParameters);

        // Generate the QR code only if ALL parameters are set
        if (!\in_array(null, $routeParameters, true)) {
            $this->addQrCodeToTemplate($template, $rewriteData, $routeParameters);
        }

        return $template->getResponse();
    }

    /**
     * Add QR code to the template.
     */
    private function addQrCodeToTemplate(BackendTemplate $template, array $rewriteData, array $routeParameters): void
    {
        try {
            $data = $this->qrCodeGenerator->generate($rewriteData, $routeParameters);

            // Create an image out of QR code so it can be easily downloaded
            $file = new File(sprintf('assets/images/q/qr-%s.svg', md5($data['url'])));
            $file->truncate();
            $file->write($data['qrCode']);
            $file->close();

            $template->qrCode = $file->path;
            $template->url = $data['url'];
        } catch (MissingMandatoryParametersException | InvalidParameterException $e) {
            $template->error = $e->getMessage();
        }
    }

    /**
     * Add form to the template.
     */
    private function addFormToTemplate(BackendTemplate $template, Request $request, array $rewriteData, array &$routeParameters): array
    {
        $formFields = [];

        // Add the scheme form field
        $formFields['scheme'] = new $GLOBALS['BE_FFL']['select'](Widget::getAttributesFromDca([
            'label' => &$GLOBALS['TL_LANG']['tl_url_rewrite']['qrCodeRef']['scheme'],
            'options' => ['http', 'https'],
        ], 'scheme', Input::post('scheme') ?: $request->getScheme()));

        // Determine the host
        if (\is_array($hosts = StringUtil::deserialize($rewriteData['requestHosts'])) && \count($hosts = array_filter($hosts)) > 0) {
            // Set the host immediately if there's only one
            if (1 === \count($hosts)) {
                $routeParameters['host'] = $hosts[0];
            } else {
                // Generate a select menu field for host
                $formFields['host'] = new $GLOBALS['BE_FFL']['select'](Widget::getAttributesFromDca([
                    'label' => &$GLOBALS['TL_LANG']['tl_url_rewrite']['qrCodeRef']['host'],
                    'options' => $hosts,
                    'eval' => ['mandatory' => true, 'includeBlankOption' => true],
                ], 'host', Input::post('host')));
            }
        } else {
            // Generate a text field for host
            $formFields['host'] = new $GLOBALS['BE_FFL']['text'](Widget::getAttributesFromDca([
                'label' => &$GLOBALS['TL_LANG']['tl_url_rewrite']['qrCodeRef']['host'],
                'eval' => ['mandatory' => true, 'decodeEntities' => true, 'rgxp' => 'url'],
            ], 'host', Input::post('host')));
        }

        $requirements = StringUtil::deserialize($rewriteData['requestRequirements']);

        // Generate the requirement fields
        if (\is_array($requirements) && \count($requirements) > 0) {
            foreach ($requirements as $requirement) {
                if ('' !== $requirement['key'] && '' !== $requirement['value']) {
                    $fieldName = 'requirement_'.$requirement['key'];

                    $formFields[$fieldName] = new $GLOBALS['BE_FFL']['text'](Widget::getAttributesFromDca([
                        'label' => sprintf($GLOBALS['TL_LANG']['tl_url_rewrite']['qrCodeRef']['requirement'], $requirement['key'], $requirement['value']),
                        'eval' => ['mandatory' => true, 'urlRewriteRequirement' => $requirement],
                    ], $fieldName, Input::post($fieldName)));

                    // Set route parameter to null value to indicate it's mandatory
                    $routeParameters[$requirement['key']] = null;
                }
            }
        }

        // Add form to template
        if (\count($formFields) > 0) {
            $formSubmit = 'contao-url-rewrite-qr-code';

            $template->formFields = $formFields;
            $template->formSubmit = $formSubmit;

            // Process the form
            if ($request->request->get('FORM_SUBMIT') === $formSubmit) {
                $this->processForm($formFields, $routeParameters);
            }
        }

        return $formFields;
    }

    /**
     * Process the form.
     */
    private function processForm(array $formFields, array &$routeParameters): array
    {
        /** @var Widget $formField */
        foreach ($formFields as $formField) {
            $formField->validate();

            // Validate the requirement regexp, if any
            if ($formField->urlRewriteRequirement && !preg_match('/'.$formField->urlRewriteRequirement['value'].'/', $formField->value)) {
                $formField->addError(sprintf($GLOBALS['TL_LANG']['tl_url_rewrite']['qrCodeRef']['requirementError'], $formField->urlRewriteRequirement['value']));
            }

            // Return an empty array if at least one field has an error
            if ($formField->hasErrors()) {
                return [];
            }

            $routeParameters[$formField->urlRewriteRequirement ? $formField->urlRewriteRequirement['key'] : $formField->name] = $formField->value;
        }

        return $routeParameters;
    }
}
