<?php

/**
 * Class Jira
 *
 * @author Manuel Will <insphare@gmail.com>
 */
class Jira {

	/**
	 * @var Auth
	 */
	private $auth;

	/**
	 * @author Manuel Will <insphare@gmail.com>
	 *
	 * @return Auth
	 */
	private function getAuth() {
		if (null === $this->auth) {
			$this->auth = new Auth(Config::get(Config::KEY_USERNAME), Config::get(Config::KEY_PASSWORD));
		}

		return $this->auth;
	}

	/**
	 * @author Manuel Will <insphare@gmail.com>
	 *
	 * @return Request
	 */
	private function getRequest() {
		$requestClass = new Request(Config::get(Config::KEY_JIRA_HOST));

		return $requestClass;
	}

	/**
	 * @author Manuel Will <insphare@gmail.com>
	 */
	public function testConnection() {
		return $this->getRequest()->setMethodGet()->setPath('/rest/api/latest/serverInfo')->get();
	}

	/**
	 * Returns profile data from current user.
	 *
	 * @return array|mixed
	 * @throws RequestException
	 * @throws UnauthorizedException
	 */
	public function getMyProfile() {
		$auth = $this->getAuth();
		$response = $this->getRequest()
			->setMethodGet()
			->setAuth($auth)
			->setPath('/rest/api/2/myself')
			->get()
		;
		return $response;
	}

	/**
	 * Search an issue by jql conditions.
	 *
	 * @param string $jql
	 * @return array|mixed
	 * @throws RequestException
	 * @throws UnauthorizedException
	 */
	public function search($jql) {
		$auth = $this->getAuth();
		$params = array(
			'jql' => $jql,
		);

		$response = $this->getRequest()
			->setMethodPost()
			->setParams($params)
			->setAuth($auth)
			->setPath('/rest/api/2/search')
			->get()
		;
		return $response;
	}

	/**
	 * Search the time tracking for current user.
	 *
	 * @return array|mixed
	 */
	public function getTimeTrackTask() {
		return $this->search(Config::get(
			Config::KEY_PROJECTS,
			Config::SUBKEY_PROJECTS_TIMETRACKING_TASK_SEARCH
		));
	}

	/**
	 * Returns issue data by issue name.
	 *
	 * @param string $issue
	 *
	 * @return array|mixed
	 *
	 * @throws RequestException
	 * @throws UnauthorizedException
	 */
	public function getIssue($issue) {
		$auth = $this->getAuth();
		$response = $this->getRequest()
			->setMethodGet()
			->setAuth($auth)
			->setPath(sprintf('/rest/api/latest/issue/%s?expand=schema,names,transitions', $issue))
			->get()
		;

		return $response;
	}

	/**
	 * Logs time for work.
	 *
	 * @author Manuel Will <insphare@gmail.com>
	 *
	 * @param string $issue
	 * @param string $duration
	 * @param string $comment
	 * @param null|string $strDateTime
	 *
	 * @return array|mixed
	 */
	public function logTime($issue, $duration, $comment, $strDateTime = null) {
		$auth = $this->getAuth();
		$response = $this->getIssue($issue);

		$internalIssueIdentifier = $response['id'];
		$date = new DateTime($strDateTime);

		$params = array(
			'comment' => $comment,
			'started' => $date->format('Y-m-d\TH:i:s.000O'),
			'timeSpent' => $duration,
		);

		$response = $this->getRequest()
			->setMethodPost()
			->setParams($params)
			->setAuth($auth)
			->setPath(sprintf('/rest/api/latest/issue/%s/worklog?adjustEstimate=auto', $internalIssueIdentifier))
			->get()
		;

		return $response;
	}

	public function getLoggedTime() {
		$collection = [];
		// startOfWeek
		$result = $this->search('worklogAuthor = manuel_will and worklogDate = "2015-11-24"');
		foreach ((array)Traversal::traverse($result, 'issues') as $issue) {
			$path = sprintf('/rest/api/latest/issue/%s/worklog', $issue['id']);
			$response = $this->getRequest()->setMethodGet()->setAuth($this->getAuth())->setPath($path)->get();
			$logs = array_map([$this, 'getWorkLog'], (array)Traversal::traverse($response, 'worklogs'));
			$collection[$issue['key']] = array_values(array_filter($logs));
		}

		// summary
		$returns = [];
		foreach ($collection as $taskNumber => $data) {
			foreach ($data as $row) {
				/** @var DateTime $dateTime */
				$dateTime = $row['created'];
				$kw = $dateTime->format('W');

				if (!isset($returns[$kw][$dateTime->format('D')])) {
					$returns[$kw][$dateTime->format('D')]['sum'] = 0.0;
				}

				if (!isset($returns[$kw][$dateTime->format('D')]['task'][$taskNumber])) {
					$returns[$kw][$dateTime->format('D')]['task'][$taskNumber] = 0.0;
				}

				$returns[$kw][$dateTime->format('D')]['task'][$taskNumber] += (float)$row['timeSpentSeconds'];
				$returns[$kw][$dateTime->format('D')]['sum'] += (float)$row['timeSpentSeconds'];
			}
		}

		return $returns;
	}

	private function getWorkLog(array $workLog) {

		$author = Traversal::traverse($workLog, 'author.name');
		if ($author != Config::get(Config::KEY_USERNAME)) {
			return false;
		}

		$dateTime = new \DateTime(Traversal::traverse($workLog, 'started'));
		$currentKw = date('W');
		$year = date('Y');
		if ($dateTime->format('W') != ($currentKw) || $dateTime->format('Y') != $year) {
			return false;
		}

		$result = [
			'comment' => Traversal::traverse($workLog, 'comment'),
			'author' => $author,
			'created'=> $dateTime,
			'timeSpentSeconds' => Traversal::traverse($workLog, 'timeSpentSeconds'),
			'timeHuman' => Traversal::traverse($workLog, 'timeSpent'),
		];

		return $result;
	}

}
