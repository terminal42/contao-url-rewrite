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
use Contao\Input;
use Contao\StringUtil;
use Contao\Validator;
use Contao\Widget;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\RouterInterface;
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
     * @var RouterInterface
     */
    private $router;

    /**
     * @var UriSigner
     */
    private $uriSigner;

    /**
     * QrCodeController constructor.
     */
    public function __construct(Connection $connection, QrCodeGenerator $qrCodeGenerator, RequestStack $requestStack, RouterInterface $router, UriSigner $uriSigner)
    {
        $this->connection = $connection;
        $this->qrCodeGenerator = $qrCodeGenerator;
        $this->requestStack = $requestStack;
        $this->router = $router;
        $this->uriSigner = $uriSigner;
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
     * @Route("/url_rewrite_qr_code/{url}", name="url_rewrite_qr_code", methods={"GET"})
     */
    public function qrCode(Request $request, string $url): Response
    {
        if (!$this->uriSigner->check($request->getSchemeAndHttpHost() . $request->getBaseUrl() . $request->getPathInfo() . (null !== ($qs = $request->server->get('QUERY_STRING')) ? '?' . $qs : ''))) {
            return new Response(Response::$statusTexts[Response::HTTP_BAD_REQUEST], Response::HTTP_BAD_REQUEST);
        }

        $url = base64_decode($url);

        if (!Validator::isUrl($url) || !preg_match('/https?:\/\//', $url)) {
            return new Response(Response::$statusTexts[Response::HTTP_BAD_REQUEST], Response::HTTP_BAD_REQUEST);
        }

        $response = new Response($this->qrCodeGenerator->generateImage($url));
        $response->headers->set('content-type', 'image/svg+xml');

        return $response;
    }

    /**
     * Add QR code to the template.
     */
    private function addQrCodeToTemplate(BackendTemplate $template, array $rewriteData, array $routeParameters): void
    {
        try {
            $url = $this->qrCodeGenerator->generateUrl($rewriteData, $routeParameters);

            $template->qrCode = $this->uriSigner->sign($this->router->generate('url_rewrite_qr_code', ['url' => base64_encode($url)], RouterInterface::ABSOLUTE_URL));
            $template->url = $url;
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
                $routeParameters = $this->processForm($formFields, $routeParameters);
            }
        }

        return $formFields;
    }

    /**
     * Process the form.
     */
    private function processForm(array $formFields, array $routeParameters): array
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
