<?php

/**
 * @file plugins/generic/counterSushi/counterSushiPlugin.inc.php
 *
 * Copyright (c) 2019 University of Pittsburgh
 * Distributed under the GNU GPL v2 or later. For full terms see the file docs/COPYING.
 *
 * @class counterSushiPlugin
 * @ingroup plugins_generic_counterSushi
 *
 * @brief COUNTER SUSHI plugin class A plugin to provide COUNTER stats through a REST API that implements the SUSHI-Lite protocol.
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class CounterSushiPlugin extends GenericPlugin{
	
	/**
	 * @copydoc Plugin::register
	 */
	public function register($category, $path, $mainContextId = NULL) {
		$success = parent::register($category, $path);
		if ($success && $this->getEnabled()) {
			HookRegistry::register('APIHandler::endpoints', [$this, 'endpoints']);
		}
		return $success;
	}
	
	/**
	 * @copydoc PKPPlugin::getDisplayName
	 */
	public function getDisplayName() {
		return __('plugins.generic.sushi.name');
	}

	/**
	 * @copydoc PKPPlugin::getDescription
	 */
	public function getDescription() {
		return __('plugins.generic.sushi.description');
	}
	
	/**
	 * Add endpoints to the stats APIHandler to serve the
	 * COUNTER reports
	 *
	 * @param string $hookName APIHandler::endpoints
	 * @param array $args [
	 * 	@option array The endpoints
	 *  @option APIHandler The handler for endpoints
	 * ]
	 */
	public function endpoints($hookName, $args) {
		$endpoints =& $args[0];
		$apiHandler = $args[1];

		if (!is_a($apiHandler, 'PKPStatsPublicationHandler')) {
			return;
		}
		array_unshift(
				$endpoints['GET'],
				[
					'pattern' => '/{contextPath}/api/{version}/stats/publications/sushi/reports',
					'handler' => [$this, 'processRequest'],
					'roles' => [ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER],
				],
				[
					'pattern' => '/{contextPath}/api/{version}/stats/publications/sushi/status',
					'handler' => [$this, 'processRequest'],
					'roles' => [ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER],
				],
				[
					'pattern' => '/{contextPath}/api/{version}/stats/publications/sushi/members',
					'handler' => [$this, 'processRequest'],
					'roles' => [ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER],
				]
				);
		$reports = $this->getReports();
		foreach ($reports as $rp) {
			array_unshift(
					$endpoints['GET'],
					[
						'pattern' => '/{contextPath}/api/{version}/stats/publications/sushi/reports/' . $rp,
						'handler' => [$this, 'processRequest'],
						'roles' => [ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER],
					]
					);
		}
	}
	
	/**
	 * Parse the args for tr_j1 COUNTER report
	 *
	 * Provides total abstract and galley views for a journal
	 * during the requested period.
	 *
	 * @param Request $slimRequest Slim request object
	 * @param APIResponse $response PSR-7 Response object
	 * @param array $args array
	 * @return APIResponse Response
	 */
	public function processRequest($slimRequest, $response, $args) {
		$request = $this->getRequest();
		$url = $request->getRequestPath();
		if (!$request->getContext()) {
			return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
		}
		$report = end(explode('/', $url));
		switch ($report) {
			case 'reports':
				return $response->withJson([
					'Report_Header' => [
						'Created' => date('c', time()),
						'Created_By' => '...',
						'Release' => 5,
					],
					'Report_Items' => $this->getReports()
				]);
			case 'status':
				return $response->withStatus(400)->withJsonError('plugins.generic.sushi.api.statusNotImplemented');
			case 'members':
				return $response->withStatus(400)->withJsonError('plugins.generic.sushi.api.membersNotImplemented');
			default:
				$availableReports = $this->getReports();
				if (in_array($report, $availableReports)) {
					$query = $slimRequest->getQueryParams();
					$defaultParams = [
						'count' => 100,
						'position_token' => 0,
					];
					$requestParams = array_merge($defaultParams, $query);
					$args = $this->parseParams($requestParams);
					if(isset($args['error'])) {
						return $response->withStatus(400)->withJsonError($args['error']);
					} else {
						$class = 'CounterReport' . strtoupper($report);
						import('plugins.generic.counterSushi.reports.' . $class);
						$counterReport = new $class();
						$rows = $counterReport->getReport($args);
						return $response->withJson([
							'Report_Header' => [
								'Created' => date('c', time()),
								'Created_By' => '...',
								'Customer_ID' => $args['customer_id'],
								'Report_ID' => strtoupper($report),
								'Release' => 5,
								'Report_Name' => '...',
								'Institution_Name' => '...',
								'Report_Filters' => [
									[
										'Name' => 'Platform',
										'Value' => '...',
									],
									[
										'Name' => 'Begin_Date',
										'Value' => $args['begin_date'],
									],
									[
										'Name' => 'End_Date',
										'Value' => $args['end_date'],
									],
									[
										'Name' => 'Data_Type',
										'Value' => 'Journal',
									],
								],
							],
							'Report_Items' => $rows
						]);
					}
				}
		}
	}
	
	/**
	 * Check if a date is valid and return its format
	 *
	 * @param string $date
	 * @return string $format
	 */
	public function validFormatDate($date) {
		$supportedFormats = ['Y', 'Y-m', 'Y-m-d'];
		foreach ($supportedFormats as $format) {
			$d = DateTime::createFromFormat($format, $date);
			if($d && $d->format($format) === $date)
				return $format;
		}
		return null;
	}
	
	/**
	 * Get names of all the reports available
	 * 
	 * @return string array
	 */
	public function getReports() {
		$reportsPath = $this->getPluginPath() . DIRECTORY_SEPARATOR . 'reports';
		$reportFiles = preg_grep('/^CounterReport/', scandir($reportsPath));
		foreach ($reportFiles as &$rf) {
			$rf = str_split(explode('.', strtolower($rf))[0], 13)[1];
		}
		unset($rf);
		return $reportFiles;
	}
	
	/**
	 * Parse the Query string to allow for proper report fetching
	 * 
	 * @param array $args A mix of default parameters and query string parameters
	 * @return mixed Array of parsed parameters ready to be sent to the Counter report
	 */
	public function parseParams($requestParams) {
		if (empty($requestParams['customer_id']) || $requestParams['customer_id'] !== 'anonymous') {
			$args = [
				'error' => 'plugins.generic.sushi.api.invalidCustomerId',
			];
		} else if (empty($requestParams['begin_date']) || is_null($beginDateFormat = $this->validFormatDate($requestParams['begin_date']))
				|| empty($requestParams['end_date']) || is_null($endDateFormat = $this->validFormatDate($requestParams['end_date']))) {
			$args = [
				'error' => 'plugins.generic.sushi.api.invalidDates',
			];
		} else if (!is_int($requestParams['count']) || $requestParams['count'] > 100 || $requestParams['count'] < 1) {
			$args = [
				'error' => 'plugins.generic.sushi.api.invalidCount',
			];
		} else if (!is_int($requestParams['position_token']) || $requestParams['position_token'] < 0) {
			$args = [
				'error' => 'plugins.generic.sushi.api.invalidPositionToken',
			];
		} else {
			$args = [
				'count' => $requestParams['count'],
				'dateStart' => DateTime::createFromFormat($beginDateFormat, $requestParams['begin_date'])->format($beginDateFormat),
				'dateEnd' => DateTime::createFromFormat($beginDateFormat, $requestParams['end_date'])->format($endDateFormat),
				'offset' => $requestParams['position_token'],
				'assocTypes' => [ASSOC_TYPE_SUBMISSION, ASSOC_TYPE_SUBMISSION_FILE],
			];
		}
		return $args;
	}
}

