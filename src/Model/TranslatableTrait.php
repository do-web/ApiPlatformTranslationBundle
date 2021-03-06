<?php

namespace Locastic\ApiPlatformTranslationBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;

/**
 * @see TranslatableInterface
 *
 * @author Gonzalo Vilaseca <gvilaseca@reiss.co.uk>
 */
trait TranslatableTrait
{
    /**
     * @var ArrayCollection|PersistentCollection|TranslationInterface[]
     */
    protected $translations;

    /**
     * @var array|TranslationInterface[]
     */
    protected $translationsCache = [];

    /**
     * @var string
     */
    protected $currentLocale;

    /**
     * Cache current translation. Useful in Doctrine 2.4+
     *
     * @var TranslationInterface
     */
    protected $currentTranslation;

    /**
     * @var string
     */
    protected $fallbackLocale;

    /**
     * TranslatableTrait constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    /**
     * @param string $locale
     *
     * @return TranslationInterface
     */
    public function getTranslation($locale = null): ?TranslationInterface
    {
        $locale = $locale ?: $this->currentLocale;
        if (null === $locale) {
            throw new \RuntimeException('No locale has been set and current locale is undefined.');
        }

        if (isset($this->translationsCache[$locale])) {
            return $this->translationsCache[$locale];
        }

        $translation = $this->translations->get($locale);
        if (null !== $translation) {
            $this->translationsCache[$locale] = $translation;

            return $translation;
        }
        if ($locale !== $this->fallbackLocale) {
            if (isset($this->translationsCache[$this->fallbackLocale])) {
                return $this->translationsCache[$this->fallbackLocale];
            }

            $fallbackTranslation = $this->translations->get($this->fallbackLocale);

            if (null !== $fallbackTranslation) {
                $this->translationsCache[$this->fallbackLocale] = $fallbackTranslation; //@codeCoverageIgnore

                return $fallbackTranslation; //@codeCoverageIgnore
            }
        }
        $translation = $this->createTranslation();
        $translation->setLocale($locale);

        $this->addTranslation($translation);

        $this->translationsCache[$locale] = $translation;

        return $translation;
    }

    /**
     * @return array
     */
    public function getTranslationLocales()
    {
        $translations = $this->getTranslations();
        $locales = [];

        foreach ($translations as $translation) {
            $locales[] = $translation->getLocale();
        }

        return $locales;
    }

    /**
     * @param string $locale
     */
    public function removeTranslationWithLocale(string $locale)
    {
        $translations = $this->getTranslations();

        foreach ($translations as $translation) {
            if ($translation->getLocale() == $locale) {
                $this->removeTranslation($translation);
            }
        }
    }

    /**
     * @return ArrayCollection|TranslationInterface[]
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @param TranslationInterface $translation
     *
     * @return bool
     */
    public function hasTranslation(TranslationInterface $translation): bool
    {
        return isset($this->translationsCache[$translation->getLocale()]) || $this->translations->containsKey(
                $translation->getLocale()
            );
    }

    /**
     * @param TranslationInterface $translation
     */
    public function addTranslation(TranslationInterface $translation): void
    {
        if (!$this->hasTranslation($translation)) {
            $this->translationsCache[$translation->getLocale()] = $translation;

            $this->translations->set($translation->getLocale(), $translation);
            $translation->setTranslatable($this);
        }
    }

    /**
     * @param TranslationInterface $translation
     */
    public function removeTranslation(TranslationInterface $translation): void
    {
        if ($this->translations->removeElement($translation)) {
            unset($this->translationsCache[$translation->getLocale()]);

            $translation->setTranslatable(null);
        }
    }

    /**
     * @param string $currentLocale
     */
    public function setCurrentLocale($currentLocale): void
    {
        $this->currentLocale = $currentLocale;
    }

    /**
     * @param string $fallbackLocale
     */
    public function setFallbackLocale($fallbackLocale): void
    {
        $this->fallbackLocale = $fallbackLocale;
    }

    /**
     * Create resource translation model.
     *
     * @return TranslationInterface
     */
    abstract protected function createTranslation();
}
