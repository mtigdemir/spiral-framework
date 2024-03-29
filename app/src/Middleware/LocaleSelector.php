<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);


namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spiral\Translator\Translator;

class LocaleSelector implements MiddlewareInterface
{
    /** @var Translator */
    private $translator;

    /** @var array */
    private $availableLocales;

    /**
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
        $this->availableLocales = $this->translator->getCatalogueManager()->getLocales();
    }

    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $locale = $this->translator->getLocale();

        try {
            foreach ($this->fetchLocales($request) as $locale) {
                if (in_array($locale, $this->availableLocales, true)) {
                    $this->translator->setLocale($locale);
                    break;
                }
            }

            return $handler->handle($request);
        } finally {
            // restore
            $this->translator->setLocale($locale);
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @return \Generator
     */
    public function fetchLocales(ServerRequestInterface $request): \Generator
    {
        $header = $request->getHeaderLine('accept-language');
        foreach (explode(',', $header) as $value) {
            if (strpos($value, ';') !== false) {
                yield substr($value, 0, strpos($value, ';'));
            }
            yield $value;
        }
    }
}
