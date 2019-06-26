<?php
/**
 * @file classes/services/QueryBuilders/PKPAuthorQueryBuilder.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPAuthorQueryBuilder
 * @ingroup query_builders
 *
 * @brief Class for building database queries for authors
 */

namespace PKP\Services\QueryBuilders;

use Illuminate\Database\Capsule\Manager as Capsule;

class PKPAuthorQueryBuilder extends BaseQueryBuilder {

	/** @var array get authors for one or more publications */
	protected $publicationIds = [];

	/** @var bool whether to return only a count of results */
	protected $countOnly = null;

	/**
	 * Set publicationIds filter
	 *
	 * @param array|int $publicationIds
	 * @return \PKP\Services\QueryBuilders\PKPAuthorQueryBuilder
	 */
	public function filterByPublicationIds($publicationIds) {
		$this->publicationIds = is_array($publicationIds) ? $publicationIds : [$publicationIds];
		return $this;
	}

	/**
	 * Whether to return only a count of results
	 *
	 * @param $enable bool
	 * @return \PKP\Services\QueryBuilders\PKPAuthorQueryBuilder
	 */
	public function countOnly($enable = true) {
		$this->countOnly = $enable;
		return $this;
	}

	/**
	 * Execute query builder
	 *
	 * @return object Query object
	 */
	public function get() {
    $this->columns = ['*'];
    $q = Capsule::table('authors as a');

		if (!empty($this->publicationIds)) {
			$q->whereIn('a.publication_id', $this->publicationIds);
		}

		// Add app-specific query statements
		\HookRegistry::call('Author::getMany::queryObject', array(&$q, $this));

		if (!empty($this->countOnly)) {
			$q->select(Capsule::raw('count(*) as author_count'));
		} else {
			$q->select($this->columns);
		}

		return $q;
	}
}
