<?php

/**
 * @file classes/site/Version.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Version
 * @ingroup site
 *
 * @see VersionDAO
 *
 * @brief Describes system version history.
 */

namespace PKP\site;

use APP\core\Application;

use Composer\Semver\Semver;

use PKP\core\Core;

class Version extends \PKP\core\DataObject
{
    /**
     * Constructor.
     */
    public function __construct(
        $major,
        $minor,
        $revision,
        $build,
        $dateInstalled,
        $current,
        $productType,
        $product,
        $productClassName,
        $lazyLoad,
        $sitewide
    ) {
        parent::__construct();

        // Initialize object
        $this->setMajor($major);
        $this->setMinor($minor);
        $this->setRevision($revision);
        $this->setBuild($build);
        $this->setDateInstalled($dateInstalled);
        $this->setCurrent($current);
        $this->setProductType($productType);
        $this->setProduct($product);
        $this->setProductClassName($productClassName);
        $this->setLazyLoad($lazyLoad);
        $this->setSitewide($sitewide);
    }

    /**
     * Compare this version with another version.
     * Returns:
     *     < 0 if this version is lower
     *     0 if they are equal
     *     > 0 if this version is higher
     *
     * @param string|Version $version the version to compare against
     *
     * @return int
     */
    public function compare($version)
    {
        if (is_object($version)) {
            return $this->compare($version->getVersionString());
        }
        return version_compare($this->getVersionString(), $version);
    }

    /**
     * Static method to return a new version from a version string of the form "W.X.Y.Z".
     *
     * @param string $versionString
     * @param string $productType
     * @param string $product
     * @param string $productClass
     * @param int $lazyLoad
     * @param int $sitewide
     *
     * @return Version
     */
    public static function fromString($versionString, $productType = null, $product = null, $productClass = '', $lazyLoad = 0, $sitewide = 1)
    {
        $versionArray = explode('.', $versionString);

        if (!$product && !$productType) {
            $application = Application::get();
            $product = $application->getName();
            $productType = 'core';
        }

        $version = new Version(
            (isset($versionArray[0]) ? (int) $versionArray[0] : 0),
            (isset($versionArray[1]) ? (int) $versionArray[1] : 0),
            (isset($versionArray[2]) ? (int) $versionArray[2] : 0),
            (isset($versionArray[3]) ? (int) $versionArray[3] : 0),
            Core::getCurrentDate(),
            1,
            $productType,
            $product,
            $productClass,
            $lazyLoad,
            $sitewide
        );

        return $version;
    }

    //
    // Get/set methods
    //

    /**
     * Get major version.
     *
     * @return int
     */
    public function getMajor()
    {
        return $this->getData('major');
    }

    /**
     * Set major version.
     *
     * @param int $major
     */
    public function setMajor($major)
    {
        $this->setData('major', $major);
    }

    /**
     * Get minor version.
     *
     * @return int
     */
    public function getMinor()
    {
        return $this->getData('minor');
    }

    /**
     * Set minor version.
     *
     * @param int $minor
     */
    public function setMinor($minor)
    {
        $this->setData('minor', $minor);
    }

    /**
     * Get revision version.
     *
     * @return int
     */
    public function getRevision()
    {
        return $this->getData('revision');
    }

    /**
     * Set revision version.
     *
     * @param int $revision
     */
    public function setRevision($revision)
    {
        $this->setData('revision', $revision);
    }

    /**
     * Get build version.
     *
     * @return int
     */
    public function getBuild()
    {
        return $this->getData('build');
    }

    /**
     * Set build version.
     *
     * @param int $build
     */
    public function setBuild($build)
    {
        $this->setData('build', $build);
    }

    /**
     * Get date installed.
     *
     * @return date
     */
    public function getDateInstalled()
    {
        return $this->getData('dateInstalled');
    }

    /**
     * Set date installed.
     *
     * @param date $dateInstalled
     */
    public function setDateInstalled($dateInstalled)
    {
        $this->setData('dateInstalled', $dateInstalled);
    }

    /**
     * Check if current version.
     *
     * @return int
     */
    public function getCurrent()
    {
        return $this->getData('current');
    }

    /**
     * Set if current version.
     *
     * @param int $current
     */
    public function setCurrent($current)
    {
        $this->setData('current', $current);
    }

    /**
     * Get product type.
     *
     * @return string
     */
    public function getProductType()
    {
        return $this->getData('productType');
    }

    /**
     * Set product type.
     *
     * @param string $productType
     */
    public function setProductType($productType)
    {
        $this->setData('productType', $productType);
    }

    /**
     * Get product name.
     *
     * @return string
     */
    public function getProduct()
    {
        return $this->getData('product');
    }

    /**
     * Set product name.
     *
     * @param string $product
     */
    public function setProduct($product)
    {
        $this->setData('product', $product);
    }

    /**
     * Get the product's class name
     *
     * @return string
     */
    public function getProductClassName()
    {
        return $this->getData('productClassName');
    }

    /**
     * Set the product's class name
     *
     * @param string $productClassName
     */
    public function setProductClassName($productClassName)
    {
        $this->setData('productClassName', $productClassName);
    }

    /**
     * Get the lazy load flag for this product
     *
     * @return bool
     */
    public function getLazyLoad()
    {
        return $this->getData('lazyLoad');
    }

    /**
     * Set the lazy load flag for this product
     *
     * @param bool $lazyLoad
     */
    public function setLazyLoad($lazyLoad)
    {
        $this->setData('lazyLoad', $lazyLoad);
    }

    /**
     * Get the sitewide flag for this product
     *
     * @return bool
     */
    public function getSitewide()
    {
        return $this->getData('sitewide');
    }

    /**
     * Set the sitewide flag for this product
     *
     * @param bool $sitewide
     */
    public function setSitewide($sitewide)
    {
        $this->setData('sitewide', $sitewide);
    }

    /**
     * Return complete version string.
     *
     * @param bool True (default) iff a numeric (comparable) version is to be returned.
     *
     * @return string
     */
    public function getVersionString($numeric = true)
    {
        $numericVersion = sprintf('%d.%d.%d.%d', $this->getMajor(), $this->getMinor(), $this->getRevision(), $this->getBuild());
        if (!$numeric && $this->getProduct() == 'omp' && preg_match('/^0\.9\.9\./', $numericVersion)) {
            return ('1.0 Beta');
        }
        if (!$numeric && $this->getProduct() == 'ojs2' && preg_match('/^2\.9\.0\./', $numericVersion)) {
            return ('3.0 Alpha 1');
        }
        if (!$numeric && $this->getProduct() == 'ojs2' && preg_match('/^2\.9\.9\.0/', $numericVersion)) {
            return ('3.0 Beta 1');
        }
        if (!$numeric && $this->getProduct() == 'ops' && preg_match('/^3\.2\.0\.0/', $numericVersion)) {
            return ('3.2.0 Beta');
        }

        return $numericVersion;
    }

    /**
     * Checks if the Version is compatible with the given string of constraints for the version,
     * formatted per composer/semver specifications;
     * c.f. https://getcomposer.org/doc/articles/versions.md#writing-version-constraints
     * Returns:
     * 		true iff the version given is compatible with this version
     * 		false iff the version given is incompatible with this version
     *
     * @param $constraints string the string of constraints for the version to be checked against
     *
     * @return boolean
     */
    public function isCompatible($constraints)
    {
        $semver = new semver();
        $version = $this->getVersionString();

        return $semver->satisfies($version, $constraints);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\PKP\site\Version', '\Version');
}
