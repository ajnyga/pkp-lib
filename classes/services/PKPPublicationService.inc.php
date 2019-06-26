<?php
/**
 * @file classes/services/PKPPublicationService.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPPublicationService
 * @ingroup services
 *
 * @brief Helper class that encapsulates business logic for publications
 */

namespace PKP\Services;

use \Core;
use \DAOResultFactory;
use \DAORegistry;
use \DBResultRange;
use \Services;
use \PKP\Services\interfaces\EntityPropertyInterface;
use \PKP\Services\interfaces\EntityReadInterface;
use \PKP\Services\interfaces\EntityWriteInterface;
use \PKP\Services\QueryBuilders\PKPPublicationQueryBuilder;
use \PKP\Services\traits\EntityReadTrait;

import('lib.pkp.classes.db.DBResultRange');

class PKPPublicationService implements EntityPropertyInterface, EntityReadInterface, EntityWriteInterface {
	use EntityReadTrait;

	/**
	 * @copydoc \PKP\Services\interfaces\EntityReadInterface::get()
	 */
	public function get($publicationId) {
		return DAORegistry::getDAO('PublicationDAO')->getById($publicationId);
	}

	/**
	 * Get publications
	 *
	 * @param array $args {
	 *		@option int|array submissionIds
	 *		@option string publisherIds
	 * 		@option int count
	 * 		@option int offset
	 * }
	 * @return array
	 */
	public function getMany($args = []) {
		$publicationQB = $this->_getQueryBuilder($args);
		$publicationQO = $publicationQB->get();
		$range = $this->getRangeByArgs($args);
		$publicationDao = DAORegistry::getDAO('PublicationDAO');
		$result = $publicationDao->retrieveRange($publicationQO->toSql(), $publicationQO->getBindings(), $range);
		$queryResults = new DAOResultFactory($result, $publicationDao, '_fromRow');

		return $queryResults->toArray();
	}

	/**
	 * @copydoc \PKP\Services\interfaces\EntityReadInterface::getMax()
	 */
	public function getMax($args = []) {
		$publicationQB = $this->_getQueryBuilder($args);
		$countQO = $publicationQB->countOnly()->get();
		$countRange = new DBResultRange($args['count'], 1);
		$publicationDao = DAORegistry::getDAO('PublicationDAO');
		$countResult = $publicationDao->retrieveRange($countQO->toSql(), $countQO->getBindings(), $countRange);
		$countQueryResults = new DAOResultFactory($countResult, $publicationDao, '_fromRow');

		return (int) $countQueryResults->getCount();
	}

	/**
	 * Build the query object for getting publications
	 *
	 * @see self::getMany()
	 * @return object Query object
	 */
	private function _getQueryBuilder($args = []) {

		$defaultArgs = [
			'contextIds' => null,
			'publisherIds' => null,
			'submissionIds' => null,
		];

		$args = array_merge($defaultArgs, $args);

		$publicationQB = new PKPPublicationQueryBuilder();
		if (!empty($args['contextIds'])) {
			$publicationQB->filterByContextIds($args['contextIds']);
		}
		if (!empty($args['publisherIds'])) {
			$publicationQB->filterByPublisherIds($args['publisherIds']);
		}
		if (!empty($args['submissionIds'])) {
			$publicationQB->filterBySubmissionIds($args['submissionIds']);
		}

		\HookRegistry::call('Publication::getMany::queryBuilder', [$publicationQB, $args]);

		return $publicationQB;
	}

	/**
	 * @copydoc \PKP\Services\interfaces\EntityPropertyInterface::getProperties()
	 */
	public function getProperties($publication, $props, $args = null) {
		$request = $args['request'];
		$dispatcher = $request->getDispatcher();

		// Get the publication's context object
		$submission = Services::get('submission')->get($publication->getData('submissionId'));
		$submissionContext = Services::get('context')->get($submission->getData('contextId'));

		$values = [];

		foreach ($props as $prop) {
			switch ($prop) {
				case '_href':
					$values[$prop] = $dispatcher->url(
						$request,
						ROUTE_API,
						$submissionContext->getData('urlPath'),
						'submissions/' . $publication->getData('submissionId') . '/publications/' . $publication->getId()
					);
					break;
				case 'authors':
					$values[$prop] = array_map(
						function($author) use ($request) {
							return Services::get('author')->getSummaryProperties($author, ['request' => $request]);
						},
						$publication->getData('authors')
					);
					break;
				case 'authorsString':
					$values[$prop] = '';
					if (isset($args['userGroups'])) {
						$values[$prop] = $publication->getAuthorString($args['userGroups']);
					}
					break;
					case 'authorsStringShort':
						$values[$prop] = $publication->getShortAuthorString();
						break;
				case 'fullTitle':
					$values[$prop] = $publication->getFullTitles();
					break;
				case 'galleys':
					$values[$prop] = array_map(
						function($galley) use ($request, $prop) {
							return Services::get('galley')->getSummaryProperties($galley, ['request' => $request]);
						},
						$publication->getData('galleys')
					);
					break;
				case 'isPublished':
					$values[$prop] = $this->isPublished($publication);
					break;

				case 'urlPublished':
					$values[$prop] = $dispatcher->url(
						$request,
						ROUTE_PAGE,
						$submissionContext->getData('urlPath'),
						'article',
						'view',
						[$submission->getBestArticleId(), 'version', $publication->getId()]
					);
					break;
				default:
					$values[$prop] = $publication->getData($prop);
					break;
			}
		}

		$values = Services::get('schema')->addMissingMultilingualValues(SCHEMA_PUBLICATION, $values, $submissionContext->getSupportedLocales());

		\HookRegistry::call('Publication::getProperties', [&$values, $publication, $props, $args]);

		ksort($values);

		return $values;
	}

	/**
	 * @copydoc \PKP\Services\interfaces\EntityPropertyInterface::getSummaryProperties()
	 */
	public function getSummaryProperties($publication, $args = null) {
		$props = Services::get('schema')->getSummaryProps(SCHEMA_PUBLICATION);

		return $this->getProperties($publication, $props, $args);
	}

	/**
	 * @copydoc \PKP\Services\interfaces\EntityPropertyInterface::getFullProperties()
	 */
	public function getFullProperties($publication, $args = null) {
		$props = Services::get('schema')->getFullProps(SCHEMA_PUBLICATION);

		return $this->getProperties($publication, $props, $args);
	}

	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityWriteInterface::validate()
	 */
	public function validate($action, $props, $allowedLocales, $primaryLocale) {
		$schemaService = Services::get('schema');

		import('lib.pkp.classes.validation.ValidatorFactory');
		$validator = \ValidatorFactory::make(
			$props,
			$schemaService->getValidationRules(SCHEMA_PUBLICATION, $allowedLocales),
			[
				'locale.regex' => __('validator.localeKey'),
			]
		);

		// Check required fields if we're adding the object
		if ($action === VALIDATE_ACTION_ADD) {
			\ValidatorFactory::required(
				$validator,
				$schemaService->getRequiredProps(SCHEMA_PUBLICATION),
				$schemaService->getMultilingualProps(SCHEMA_PUBLICATION),
				$primaryLocale
			);
		}

		// Check for input from disallowed locales
		\ValidatorFactory::allowedLocales($validator, $schemaService->getMultilingualProps(SCHEMA_PUBLICATION), $allowedLocales);

		// The submissionId must match an existing submission
		$validator->after(function($validator) use ($props) {
			if (isset($props['submissionId']) && !$validator->errors()->get('submissionId')) {
				$submission = Services::get('submission')->get($props['submissionId']);
				if (!$submission) {
					$validator->errors()->add('submissionId', __('publication.invalidSubmission'));
				}
			}
		});

		// Don't allow an empty value for the primary locale of the title field
		\ValidatorFactory::requirePrimaryLocale(
			$validator,
			['title'],
			$props,
			$allowedLocales,
			$primaryLocale
		);

		if ($validator->fails()) {
			$errors = $schemaService->formatValidationErrors($validator->errors(), $schemaService->get(SCHEMA_PUBLICATION), $allowedLocales);
		}

		\HookRegistry::call('Publication::validate', [&$errors, $action, $props, $allowedLocales, $primaryLocale]);

		return $errors;
	}

	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityWriteInterface::add()
	 */
	public function add($publication, $request) {
		$publication->setData('lastModified', Core::getCurrentDate());
		$publicationId = DAORegistry::getDAO('PublicationDAO')->insertObject($publication);
		$publication = $this->get($publicationId);

		\HookRegistry::call('Publication::add', [$publication, $request]);

		return $publication;
	}

	/**
	 * Create a new version of a publication
	 *
	 * Make a copy of an existing publication, without the datePublished,
	 * and make copies of all associated objects.
	 *
	 * @param Publication $publication The publication to copy
	 * @param Request
	 * @return Publication The new publication
	 */
	public function version($publication, $request) {
		$newPublication = clone $publication;
		$newPublication->setData('id', null);
		$newPublication->setData('datePublished', null);
		$newPublication->setData('lastModified', Core::getCurrentDate());
		$newPublication = $this->add($newPublication, $request);

		$contributors = $publication->getData('contributors');
		if (!empty($contributors)) {
			foreach ($contributors as $contributor) {
				$newContributor = clone $contributor;
				$newContributor->setData('id', null);
				$newContributor->setData('publicationId', $newPublication->getId());
				Services::get('author')->add($newContributor, $request);
			}
		}

		$newPublication = $this->get($newPublication->getId());

		\HookRegistry::call('Publication::version', [$newPublication, $publication, $request]);

		return $newPublication;
	}

	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityWriteInterface::edit()
	 */
	public function edit($publication, $params, $request) {
		$publicationDao = DAORegistry::getDAO('PublicationDAO');

		$newPublication = $publicationDao->newDataObject();
		$newPublication->_data = array_merge($publication->_data, $params);
		$newPublication->stampModified();

		\HookRegistry::call('Publication::edit', [$newPublication, $publication, $params, $request]);

		$publicationDao->updateObject($newPublication);
		$newPublication = $this->get($newPublication->getId());

		return $newPublication;
	}

	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityWriteInterface::delete()
	 */
	public function delete($publication) {
		\HookRegistry::call('Publication::delete::before', [$publication]);

		DAORegistry::getDAO('PublicationDAO')->deleteObject($publication);

		$contributors = Services::get('author')->getMany(['publicationIds' => $publication->getId()]);
		foreach ($contributors as $contributor) {
			Services::get('author')->delete($contributor);
		}

		\HookRegistry::call('Publication::delete', [$publication]);
	}

	/**
	 * Is this publication published?
	 *
	 * @param Publication $publication
	 * @param array $dependencies
	 * @return boolean
	 */
	public function isPublished($publication, $dependencies = []) {
		$datePublished = $publication->getData('datePublished');
		return $datePublished && strtotime($datePublished) < Core::getCurrentDate();
	}
}
