<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2010 DMM Websolutions
*  (c) 2011-2012 Kevin Renskers
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(PATH_t3lib.'mail/class.t3lib_mail_message.php');
require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('lang').'lang.php');

/**
 * Plugin 'JobControl' for the 'dmmjobcontrol' extension.
 *
 * @author Kevin Renskers
 * @author Jan Wolters
 * @package TYPO3
 * @subpackage tx_dmmjobcontrol
 */

class tx_dmmjobcontrol_pi1 extends tslib_pibase {
	var $prefixId = 'tx_dmmjobcontrol_pi1'; // Same as class name
	var $scriptRelPath = 'pi1/class.tx_dmmjobcontrol_pi1.php'; // Path to this script relative to the extension dir.
	var $extKey = 'dmmjobcontrol';	// The extension key.
	var $pi_checkCHash = true;
	var $flexform = false;
	var $conf = false;
	var $startpoint;
	var $sysfolders = array();
	var $sys_language_mode;
	var $rssMode = false;
	var $requiredFields = array();

	/**
	 * The main method that gets called when the plugin is showed in the frontend.
	 * This function will find out what to show on screen, call the approprioate functions and return the html
	 *
	 * @param string $content The plugin content
	 * @param array $conf The plugin configuration (TS)
	 * @return string The content that is displayed on the website
	 */
	function main($content, $conf)	{
		$this->conf = $conf; //store configuration
		$this->pi_loadLL(); // Loading language-labels
		$this->pi_setPiVarDefaults(); // Set default piVars from TS
		$this->pi_initPIflexForm(); // Init FlexForm configuration for plugin

		// Disable caching
		$this->pi_USER_INT_obj = 1;
		$GLOBALS['TSFE']->set_no_cache();

		// Load the complete TCA array into the global var $TCA, so we can find out what type a certain field is etc.
		// This will also include the config of user-created fields in extending plugins
		$GLOBALS['TSFE']->includeTCA();
		t3lib_div::loadTCA('tx_dmmjobcontrol_job');

		// Get the PID of the sysfolder where everything will be stored.
		if (!is_null($this->cObj->data['pages'])) { // First look for 'startingpoint' config in the plugin
			$this->startpoint = $this->cObj->data['pages'];
		} elseif ($storagePid = $GLOBALS['TSFE']->getStorageSiterootPids()) { // No startingpoint given, is there a storagepid given?
			$this->startpoint = $storagePid['_STORAGE_PID'];
		} else {																																 // Last resort: the current page itself
			$this->startpoint = $GLOBALS['TSFE']->id;
		}

		// Recursively find all storage pages
		$this->recursive = 0;
		if (isset($this->cObj->data['recursive']) && $this->cObj->data['recursive']) {
			$this->recursive = $this->cObj->data['recursive'];
		} elseif (isset($this->conf['recursive']) && $this->conf['recursive']) {
			$this->recursive = $this->conf['recursive'];
		}

		$this->sysfolders[] = $this->startpoint;
		if ($this->recursive) {
			$this->getSysFolders($this->startpoint);
		}

		// Default whereadd statement added to all queries, so we don't show deleted jobs, or jobs that can't be shown yet/anymore
		$this->whereAdd  = 'tx_dmmjobcontrol_job.pid IN ('.implode(',', $this->sysfolders).') AND tx_dmmjobcontrol_job.deleted=0 AND tx_dmmjobcontrol_job.starttime<='.time().' AND (tx_dmmjobcontrol_job.endtime=0 OR tx_dmmjobcontrol_job.endtime>'.time().')';

		// Check if hidden jobs should be shown
		if (!$this->conf['ignore_hidden']) {
			$this->whereAdd .= ' AND tx_dmmjobcontrol_job.hidden=0';
		}

		$this->whereAdd .=' AND tx_dmmjobcontrol_job.sys_language_uid='.$GLOBALS['TSFE']->sys_language_content;

		// "display" decides what is rendered: codes can be set by TS or FlexForm with priority on FlexForm
		$displayCodesFlexForm = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'what_to_display', 'sDEF');
		$displayCodes = $displayCodesFlexForm ? $displayCodesFlexForm : $this->cObj->stdWrap($this->conf['display'], $this->conf['display.']);
		$displayCodesArray = t3lib_div::trimExplode(',', $displayCodes, 1);

		// If we came here from the search form, then save the searchvalues in the session so we can get them later
		if (isset($this->piVars['search_submit'])) {
			foreach ($this->piVars['search'] AS $field => $value) {
				if (!(is_null($value) && strlen($value)) || is_array($value)) {
					$searchArray['search'][$field] = $value;
				}
			}
			$GLOBALS['TSFE']->fe_user->setKey('ses', $this->prefixId, $searchArray);

			// Redirect to the list page to solve the expired page problem
			$listurl = $this->cObj->getTypoLink_URL($this->conf['pid.']['list'] ? $this->conf['pid.']['list'] : $GLOBALS['TSFE']->id);
			header('Location: /'.$listurl);
		}

		// Reset the search
		if (isset($this->piVars['reset_submit'])) {
			$GLOBALS['TSFE']->fe_user->setKey('ses', $this->prefixId, array());
		}

		// For each display code call the function that goes with it
		if (count($displayCodesArray)) {
			foreach ($displayCodesArray AS $displayCode) {
				$displayCode = (string)strtoupper(trim($displayCode));
				$this->display = $displayCode;
				switch ($displayCode) {
					case 'LIST':
						$content .= $this->displayList($this->conf['template.']['list']);
					break;

					case 'DETAIL':
						$content .= $this->displayDetail();
					break;

					case 'APPLY':
						$content .= $this->displayDetail(true);
					break;

					case 'SEARCH':
						$content .= $this->displaySearch();
					break;

					case 'RSS':
						$this->rssMode = true;
						header('Content-Type: text/xml');
						$charset = isset($GLOBALS['TSFE']->config['config']['renderCharset']) ? $GLOBALS['TSFE']->config['config']['renderCharset'] : 'iso-8859-1';
						$content .= '<?xml version="1.0" encoding="'.$charset.'"?>'.$this->displayList($this->conf['template.']['rss']);
					break;

					default:
						$content .= $this->displayHelp();
					break;
				}
			}
		} else {
			$content .= $this->displayHelp();
		}

		if ($this->rssMode || $this->conf['wrap_in_base_class'] == '0') {
			return $content;
		} else {
			return $this->pi_wrapInBaseClass($content);
		}
	}

	/**
	 * Recursively get page id's
	 *
	 * @param int $uid Page uid
	 * @return void
	 */
	function getSysFolders($uid) {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid',
			'pages',
			'pid='.$uid.' AND deleted=0 AND hidden=0',
			'',
			'',
			''
		);

		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$this->getSysFolders($row['uid']);
			$this->sysfolders[] = $row['uid'];
		}
	}

	/**
	 * The function to display a list with jobs, will also do the search for jobs if that is needed
	 *
	 * @param string $templateConf Which template to use
	 * @return string The content that is displayed on the website
	 */
	function displayList($templateConf) {
		// Get the template
		$this->templateCode = $this->cObj->fileResource($templateConf);
		if (is_null($this->templateCode)) {
			return $this->pi_getLL('template_not_found');
		}

		// Get the parts out of the template
		$template['total'] = $this->cObj->getSubpart($this->templateCode, '###TEMPLATE###');
		$template['rss'] = $this->cObj->getSubpart($template['total'], '###RSS_IMAGE_TEMPLATE###');
		$template['rows'] = $this->cObj->getSubpart($template['total'], '###ROWS###');
		$template['row'] = $this->cObj->getSubpart($template['rows'], '###ROW###');
		$template['row_alt'] = $this->cObj->getSubpart($template['rows'], '###ROW_ALT###');
		if (empty($template['row_alt'])) {
			$template['row_alt'] = $template['row'];
		}

		// Start making the query. Store all the query parts in arrays
		$tableAdd[] = 'tx_dmmjobcontrol_job';
		$selectAdd[] = 'tx_dmmjobcontrol_job.*';
		$whereAdd[] = $this->whereAdd;

		// If there is a search-session, then extend the query arrays to make the search (but not for rss feeds of course)
		if (!$this->rssMode && !$this->conf['ignore_search']) {
			$session = $GLOBALS['TSFE']->fe_user->getKey('ses', $this->prefixId);
			if (isset($session['search']) && $search = $session['search']) {
				global $TCA;

				foreach ($search AS $field => $value) {
					if (is_array($value) && count($value) == 1 && current($value) == -1) {
						continue;
					}

					if (isset($TCA['tx_dmmjobcontrol_job']['columns'][$field]['config']['MM'])) {
						$table = $TCA['tx_dmmjobcontrol_job']['columns'][$field]['config']['MM'];
						$tableAdd[] = $table;
						$whereAdd[] = $table.'.uid_local=tx_dmmjobcontrol_job.uid AND ('.$table.'.uid_foreign='.implode(' OR '.$table.'.uid_foreign=', $value).')';

					} elseif ($field == 'keyword') {
						$keywords = str_replace(array(','), ' ', $value);
						$keywords = explode(' ', $keywords);

						foreach ($keywords as $keyword) {
							$keyword = addslashes($keyword);

							$whereAdd[] = '(tx_dmmjobcontrol_job.job_title LIKE "%'.$keyword.'%" OR '.
							'tx_dmmjobcontrol_job.job_description LIKE "%'.$keyword.'%" OR '.
							'tx_dmmjobcontrol_job.location LIKE "%'.$keyword.'%" OR '.
							'tx_dmmjobcontrol_job.reference LIKE "%'.$keyword.'%" OR '.
							'tx_dmmjobcontrol_job.job_requirements LIKE "%'.$keyword.'%")';
						}
					} elseif (isset($TCA['tx_dmmjobcontrol_job']['columns'][$field])) {
					// !!!
					// listQuery doesn't do IN statement
					// !!!
					$value = current($value);
						$selectAdd[] = 'tx_dmmjobcontrol_job.'.$field.' AS '.$field;
						$whereAdd[] = $GLOBALS['TYPO3_DB']->listQuery($field, $value, 'tx_dmmjobcontrol_job');
					} else {
						continue;
					}
				}
			}
		}
		
		// Is there an extra whereAdd given in the TypoScript code?
		if (isset($this->conf['whereadd']) && $this->conf['whereadd']) {
			$whereAdd[] = $this->conf['whereadd'];
		}
		
		// Set limit on query
		$limit = $this->conf['limit'] ? $this->conf['limit'] : '';

		// Extend limit for page browser (not for rss feeds)
		if (!$this->rssMode && isset($this->conf['paged']) && $this->conf['paged'] && isset($this->conf['limit']) && $this->conf['limit']) {
			if (!(isset($this->piVars['page']) && $this->piVars['page'])) {
				$this->piVars['page'] = 1;
			}
			$limit = (($this->piVars['page'] - 1) * $this->conf['limit']).', '.$limit;
		}

		$sort = $this->conf['sort'] ? $this->conf['sort'] : 'crdate DESC';

		// Finally exexute the query
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			implode(', ', $selectAdd),
			implode(', ', $tableAdd),
			implode(' AND ', $whereAdd),
			'tx_dmmjobcontrol_job.uid',
			$sort,
			$limit
		);

		// Get all the jobs, and put them in the template
		$i = 0;
		$content = '';

		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$markerArray = $this->getJobData($row);

			if ($i%2 && !$this->rssMode) {
				$content .= $this->cObj->substituteMarkerArrayCached($template['row_alt'], $markerArray);
			} else {
				$content .= $this->cObj->substituteMarkerArrayCached($template['row'], $markerArray);
			}
			
			$i++;
		}

		$enablePageBrowser = true;
		if (!$content) {
			$content = $this->pi_getLL('no_jobs_found');
			$enablePageBrowser = false;
		}

		$markerArray = $this->getLabels();
		$wrappedMarkerArray['###ROWS###'] = $content;
		$wrappedMarkerArray['###PAGEBROWSER###'] = '';
		$wrappedMarkerArray['###RSS_IMAGE_TEMPLATE###'] = '';

		// Paged lists (not for rss feeds)
		if ($enablePageBrowser && !$this->rssMode && isset($this->conf['paged']) && $this->conf['paged'] && isset($this->conf['limit']) && $this->conf['limit']) {
			$template['pagebrowser'] = $this->cObj->getSubpart($template['total'], '###PAGEBROWSER###');
			$wrappedMarkerArray['###PAGEBROWSER###'] = $this->getPageBrowser($template['pagebrowser'], $tableAdd, $whereAdd);
		}

		// Show the rss logo image
		if ($this->rssMode && isset($this->conf['rss.']['image']) && $this->conf['rss.']['image']) {
			$wrappedMarkerArray['###RSS_IMAGE_TEMPLATE###'] = $this->cObj->substituteMarkerArrayCached($template['rss'], $markerArray);
		}

		return $this->cObj->substituteMarkerArrayCached($template['total'], $markerArray, $wrappedMarkerArray);
	}

	/**
	 * The function to display the detailview of one job
	 *
	 * @param bool $applyOnly Show only the apply form?
	 * @return string The content that is displayed on the website
	 */
	function displayDetail($applyOnly = false) {
		if (isset($this->piVars['ref']) && $this->piVars['ref']) {
			// Find the job_uid by searching the refence number
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_dmmjobcontrol_job', 'reference='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->piVars['ref'], 'tx_dmmjobcontrol_job').' AND '.$this->whereAdd);
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$this->piVars['job_uid'] = $row['uid'];
			}
		}

		if (isset($this->piVars['job_uid']) && $this->piVars['job_uid']) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_dmmjobcontrol_job', 'uid='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->piVars['job_uid'], 'tx_dmmjobcontrol_job').' AND '.$this->whereAdd);
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				// This sets the jobtitle as the page title (also for use in indexed search results)
				if (isset($this->conf['substitutePageTitle']) && $this->conf['substitutePageTitle']) {
					$GLOBALS['TSFE']->indexedDocTitle = $row['job_title'];
					$GLOBALS['TSFE']->page['title'] = $row['job_title'];
					$this->cObj->LOAD_REGISTER(array('JOBTITLE' => $row['job_title']), '');
					$this->cObj->LOAD_REGISTER(array('JOBLOCATION' => $row['location']), '');
				}

				// Which fields are required?
				if (isset($this->conf['apply.']['required']) && $this->conf['apply.']['required']) {
					$this->requiredFields = t3lib_div::trimExplode(',', $this->conf['apply.']['required']);
				}

				// Get all the labels and job data
				$labels = $this->getLabels();
				$jobData = $this->getJobData($row);

				if (isset($this->conf['hide_empty']) && $this->conf['hide_empty']) {
					$markerArray = $this->hideEmpty($labels, $jobData, $row);
				} else {
					$markerArray = $labels + $jobData;
				}

				// Process the form: send out email
				if (isset($this->piVars['apply_submit'])) {
					// Get the appy templates
					$this->templateCode = $this->cObj->fileResource($this->conf['template.']['apply']);
					if (is_null($this->templateCode)) {

						return $this->pi_getLL('template_not_found');
					}

					// Spamblock?
					if (isset($this->conf['apply.']['spamblock']) && $this->conf['apply.']['spamblock']) {
						$session = $GLOBALS['TSFE']->fe_user->getKey('ses', $this->prefixId);

						if (!is_array($session['spamblock']) || !$this->piVars['sessioncheck'] || !in_array($this->piVars['sessioncheck'], $session['spamblock'])) {
							$template['thanks'] = $this->cObj->getSubpart($this->templateCode, '###APPLY_THANKS_TEMPLATE###');
							return $this->cObj->substituteMarkerArrayCached($template['thanks'], $markerArray);
						}
					}

					// Extend the markerArray with the posted values
					$markerArray['###FULLNAME_VALUE###'] = $this->piVars['apply']['fullname'];
					$markerArray['###EMAIL_VALUE###'] = $this->piVars['apply']['email'];
					$markerArray['###MOTIVATION_VALUE###'] = $this->piVars['apply']['motivation'];

					if (isset($this->conf['htmlmail']) && $this->conf['htmlmail']) {
						$markerArray['###MOTIVATION_VALUE###'] = nl2br($this->piVars['apply']['motivation']);
					}

					$subject = $this->pi_getLL('apply_email_subject').$row['job_title'];

					// HTML email body
					$template['htmlEmail'] = $this->cObj->getSubpart($this->templateCode, '###HTML_EMAIL_TEMPLATE###');
					$htmlBody  = $this->cObj->substituteMarkerArrayCached($template['htmlEmail'], $markerArray);

					// Plain text email boby
					$template['textEmail'] = $this->cObj->getSubpart($this->templateCode, '###TEXT_EMAIL_TEMPLATE###');
					$textBody  = $this->cObj->substituteMarkerArrayCached($template['textEmail'], $markerArray);

					// The uploaded CV
					if (isset($_FILES['tx_dmmjobcontrol_pi1']['name']['apply']['cv']) && $_FILES['tx_dmmjobcontrol_pi1']['name']['apply']['cv']) {
						// Add attachment to email
						$fileInfo = pathinfo($_FILES['tx_dmmjobcontrol_pi1']['name']['apply']['cv']);
						if (t3lib_div::inList('doc,docx,pdf,odt,sxw,rtf,zip,DOC,DOCX,PDF,ODT,SXW,RTF,ZIP', $fileInfo['extension']) && t3lib_div::verifyFilenameAgainstDenyPattern($_FILES['tx_dmmjobcontrol_pi1']['name']['apply']['cv'])) {
							$source = $_FILES['tx_dmmjobcontrol_pi1']['tmp_name']['apply']['cv'];
							$destination = PATH_site.'typo3temp/'.basename($_FILES['tx_dmmjobcontrol_pi1']['name']['apply']['cv']);
							t3lib_div::upload_copy_move($source, $destination);
						} else {
							$htmlBody .= '<p><i>The uploaded CV "'.$_FILES['tx_dmmjobcontrol_pi1']['name']['apply']['cv'].'" was not attached because it was not in a valid file format.</i></p>';
							$textBody .= "\n\nThe uploaded CV ".$_FILES['tx_dmmjobcontrol_pi1']['name']['apply']['cv']." was not attached because it was not in a valid file format.";
						}
					}

					// The uploaded soll. letter
					if (isset($_FILES['tx_dmmjobcontrol_pi1']['name']['apply']['letter']) && $_FILES['tx_dmmjobcontrol_pi1']['name']['apply']['letter']) {
						// Add attachment to email
						$fileInfo = pathinfo($_FILES['tx_dmmjobcontrol_pi1']['name']['apply']['letter']);
						if (t3lib_div::inList('doc,docx,pdf,odt,sxw,rtf,zip,DOC,DOCX,PDF,ODT,SXW,RTF,ZIP', $fileInfo['extension']) && t3lib_div::verifyFilenameAgainstDenyPattern($_FILES['tx_dmmjobcontrol_pi1']['name']['apply']['letter'])) {
							$source2 = $_FILES['tx_dmmjobcontrol_pi1']['tmp_name']['apply']['letter'];
							$destination2 = PATH_site.'typo3temp/'.basename($_FILES['tx_dmmjobcontrol_pi1']['name']['apply']['letter']);
							t3lib_div::upload_copy_move($source2, $destination2);
						} else {
							$htmlBody .= '<p><i>The uploaded letter "'.$_FILES['tx_dmmjobcontrol_pi1']['name']['apply']['letter'].'" was not attached because it was not in a valid file format.</i></p>';
							$textBody .= "\n\nThe uploaded letter ".$_FILES['tx_dmmjobcontrol_pi1']['name']['apply']['letter']." was not attached because it was not in a valid file format.";
						}
					}

					// Is there a contact person added to the job?
					if ($row['contact']) {
						$contact_res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', ' tx_dmmjobcontrol_contact', 'uid='.$row['contact']);
						if ($contact_row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($contact_res)) {
							$this->conf['apply.']['to'] = $contact_row['email'];
						}
					}

					if (isset($this->conf['htmlmail']) && $this->conf['htmlmail']) {
						// Send HTML email with CV and letter as attachment
						$htmlmailClass = t3lib_div::makeInstance('t3lib_mail_message');

						$htmlmailClass->setFrom(array($this->conf['apply.']['to']=> $this->pi_getLL('mail_from_name')));
						$htmlmailClass->setReplyTo(array($this->conf['apply.']['to']=> $this->pi_getLL('mail_from_name')));
						$htmlmailClass->setTo($this->conf['apply.']['to']);
						$htmlmailClass->setSubject($subject);
						$htmlmailClass->setBody($body);

						// Add CV as attachment
						if (isset($destination)) {
							$htmlmailClass->attach(Swift_Attachment::fromPath($destination));
						}

						// Add letter as attachment
						if (isset($destination2)) {
							$htmlmailClass->attach(Swift_Attachment::fromPath($destination2));
						}

						$htmlmailClass->setBody($htmlBody, 'text/html');
						$htmlmailClass->addPart($textBody, 'text/plain');

						$htmlmailClass->send();

						if (isset($destination)) {
							t3lib_div::unlink_tempfile($destination);
						}
						if (isset($destination2)) {
							t3lib_div::unlink_tempfile($destination2);
						}
					} else {
						// Send plain text email, CV and letter as download links
						if (isset($destination)) {
							$textBody .= "\n\nUploaded CV: ".$GLOBALS['TSFE']->baseUrlWrap('/typo3temp/'.basename($_FILES['tx_dmmjobcontrol_pi1_cv']['name']));
						}

						if (isset($destination2)) {
							$textBody .= "\n\nUploaded letter: ".$GLOBALS['TSFE']->baseUrlWrap('/typo3temp/'.basename($_FILES['tx_dmmjobcontrol_pi1']['name']['apply']['letter']));
						}

						$this->cObj->sendNotifyEmail($subject."\n".$textBody, $this->conf['apply.']['to'], '', $this->conf['apply.']['to'], 'JobControl job application');
					}

					if (isset($this->conf['apply.']['redirect']) && $this->conf['apply.']['redirect']) {
						// redirect to given page
						header('Location: /'.$this->cObj->getTypoLink_URL($this->conf['apply.']['redirect']));
						exit;
					}

					// The thank-you page
					$template['thanks'] = $this->cObj->getSubpart($this->templateCode, '###APPLY_THANKS_TEMPLATE###');
					return $this->cObj->substituteMarkerArrayCached($template['thanks'], $markerArray);
				} else {
					// Show the detail page / apply form

					// Get the template
					if ($applyOnly) {
						$this->templateCode = $this->cObj->fileResource($this->conf['template.']['apply']);
					} else {
						$this->templateCode = $this->cObj->fileResource($this->conf['template.']['detail']);
					}

					if (is_null($this->templateCode)) {
						return $this->pi_getLL('template_not_found');
					}

					// Get the main part out of the template
					$template['total'] = $this->cObj->getSubpart($this->templateCode, '###TEMPLATE###');

					if ($applyOnly) {
						// If we only have to show the apply form, then we are already done now
						return $this->getApplyFormData($template['total'], $markerArray);
					}

					// On the detail page we have to fill the APPLY part of the template
					$wrappedMarkerArray['###APPLY###'] = '';
					if (isset($this->conf['apply.']['form']) && $this->conf['apply.']['form']) {
						$applyForm = '';
						if ($this->conf['apply.']['form'] == 1) {
							$applyForm = $this->cObj->fileResource($this->conf['template.']['apply']);
							$applyForm = $this->cObj->getSubpart($applyForm, '###TEMPLATE###');
						}

						$template['apply'] = $this->cObj->getSubpart($template['total'], '###APPLY###');
						$template['apply'] = $this->cObj->substituteMarkerArrayCached($template['apply'], array('###INCLUDE_APPLY_FORM###'=>$applyForm));

						$wrappedMarkerArray['###APPLY###'] = $this->getApplyFormData($template['apply'], $markerArray);
					}

					return $this->cObj->substituteMarkerArrayCached($template['total'], $markerArray, $wrappedMarkerArray);
				}
			}
		}

		// Job not found or job_uid not set at all
		return $this->notFound();
	}

	/**
	 * Our "job not found" handler
	 *
	 * @param void
	 * @return string|void The content that is displayed on the website, if a redirect is not done
	 */
	function notFound() {
		if ($this->conf['notfound.']['statuscode']) {
			// Give status code
			header('HTTP/1.0 '.$this->conf['notfound.']['statuscode']);
		}

		if ($this->conf['notfound.']['handler']) {
			// Custom handler
			if (is_numeric($this->conf['notfound.']['handler'])) {
				// Redirect to PID
				header('Location: /'.$this->cObj->getTypoLink_URL($this->conf['notfound.']['handler']));
			} else {
				// Just show as text
				return $this->conf['notfound.']['handler'];
			}
		} else {
			// Default handler
			return $this->pi_getLL('job_not_found');
		}
	}

	/**
	 * The function to display the searchform
	 *
	 * @param void
	 * @return string The content that is displayed on the website
	 */
	function displaySearch() {
		// Get the template
		$this->templateCode = $this->cObj->fileResource($this->conf['template.']['search']);
		if (is_null($this->templateCode)) {
			return $this->pi_getLL('template_not_found');
		}

		// Get the parts out of the template
		$template['total'] = $this->cObj->getSubpart($this->templateCode, '###TEMPLATE###');

		// Get all the labels and the form data
		$markerArray = $this->getLabels() + $this->getFormData();

		return $this->cObj->substituteMarkerArrayCached($template['total'], $markerArray);
	}

	/**
	 * The function to display some help if something went wrong (wrong "display" code set, or not set at all)
	 *
	 * @param void
	 * @return string The content that is displayed on the website
	 */
	function displayHelp() {
		return $this->pi_getLL('wrong_display_code');
	}

	/**
	 * Parse all the multi-language markers
	 *
	 * @param void
	 * @return array Array containing all the markers and their values
	 */
	function getLabels() {
		$labelMarkers = array(
			'CRDATE_LABEL',
			'REFERENCE_LABEL',
			'JOB_TITLE_LABEL',
			'EMPLOYER_LABEL',
			'EMPLOYER_DESCRIPTION_LABEL',
			'LOCATION_LABEL',
			'SHORT_JOB_DESCRIPTION_LABEL',
			'JOB_DESCRIPTION_LABEL',
			'EXPERIENCE_LABEL',
			'JOB_REQUIREMENTS_LABEL',
			'JOB_BENEFITS_LABEL',
			'APPLY_INFORMATION_LABEL',
			'SALARY_LABEL',
			'JOB_TYPE_LABEL',
			'CONTRACT_TYPE_LABEL',
			'REGION_LABEL',
			'SECTOR_LABEL',
			'CATEGORY_LABEL',
			'DISCIPLINE_LABEL',
			'EDUCATION_LABEL',
			'SEARCH_LABEL',
			'RESET_LABEL',
			'BACKTOLIST',
			'BACKTOJOB',
			'KEYWORD_LABEL',
			'APPLY_HEADER',
			'FULLNAME_LABEL',
			'EMAIL_LABEL',
			'APPLY_LABEL',
			'MOTIVATION_LABEL',
			'CV_LABEL',
			'LETTER_LABEL',
			'APPLY_LINK',
			'APPLY_THANKS',
			'CONTACT_NAME_LABEL',
			'CONTACT_ADDRESS_LABEL',
			'CONTACT_PHONE_LABEL',
			'CONTACT_EMAIL_LABEL'
		);

		// Extend $labelMarkers with user function?
		if (isset($this->conf['labelMarkersFunction']) && $this->conf['labelMarkersFunction']) {
			$funcConf = $this->conf['labelMarkersFunction.'];
			$funcConf['parent'] = & $this;
			$labelMarkers = $this->cObj->callUserFunction($this->conf['labelMarkersFunction'], $funcConf, $labelMarkers);
		}

		// Get the label from the locallang.xml file, and apply the stdWrap configuration
		foreach ($labelMarkers AS $labelMarker) {
			$markerArray['###'.$labelMarker.'###'] = $this->cObj->stdWrap($this->pi_getLL(strtolower($labelMarker)), $this->conf[strtolower($labelMarker).'_stdWrap.']);
		}

		// The labels for required apply form fields get one more stdWrap configuration
		if (!isset($this->piVars['apply_submit'])) {
			foreach ($this->requiredFields AS $requiredField) {
				$markerArray['###'.strtoupper($requiredField).'_LABEL###'] = $this->cObj->stdWrap($markerArray['###'.strtoupper($requiredField).'_LABEL###'], $this->conf['apply_required_stdWrap.']);
			}
		}

		// Some extra markers that don't come from the locallang.xml file
		$markerArray['###RSS_TITLE###'] = $this->cObj->stdWrap($this->conf['rss.']['title'], $this->conf['rss_title_stdWrap.']);
		$markerArray['###RSS_DESCRIPTION###'] = $this->cObj->stdWrap($this->conf['rss.']['description'], $this->conf['rss_description_stdWrap.']);
		$markerArray['###RSS_IMAGE###'] = $GLOBALS['TSFE']->baseUrlWrap($this->cObj->IMG_RESOURCE(array('file' => $this->conf['rss.']['image'])));
		$markerArray['###LINKTOLIST###'] = $GLOBALS['TSFE']->baseUrlWrap($this->cObj->getTypoLink_URL($this->conf['pid.']['list']?$this->conf['pid.']['list']:$GLOBALS['TSFE']->id));
		$markerArray['###LANGUAGE###'] = $GLOBALS['TSFE']->config['config']['language'];

		return $markerArray;
	}

	/**
	 * Get all the markers to insert into the search form
	 *
	 * @param void
	 * @return array Array containing all the markers and their values
	 */
	function getFormData() {
		$markerArray['###FORM_ATTRIBUTES###'] = ' action="'.$this->cObj->getTypoLink_URL($this->conf['pid.']['list']?$this->conf['pid.']['list']:$GLOBALS['TSFE']->id).'" method="post" ';
		$markerArray['###SECTOR_SELECT###'] = $this->getFormSelect('sector');
		$markerArray['###REGION_SELECT###'] = $this->getFormSelect('region');
		$markerArray['###CATEGORY_SELECT###'] = $this->getFormSelect('category');
		$markerArray['###DISCIPLINE_SELECT###'] = $this->getFormSelect('discipline');
		$markerArray['###EDUCATION_SELECT###'] = $this->getFormSelect('education');
		$markerArray['###CONTRACT_TYPE_SELECT###'] = $this->getFormSelect('contract_type');
		$markerArray['###JOB_TYPE_SELECT###'] = $this->getFormSelect('job_type');
		$markerArray['###SEARCH_NAME###'] = 'tx_dmmjobcontrol_pi1[search_submit]';
		$markerArray['###RESET_NAME###'] = 'tx_dmmjobcontrol_pi1[reset_submit]';
		$markerArray['###FULLNAME_NAME###'] = 'tx_dmmjobcontrol_pi1[apply][fullname]';
		$markerArray['###EMAIL_NAME###'] = 'tx_dmmjobcontrol_pi1[apply][email]';
		$markerArray['###KEYWORD_NAME###'] = 'tx_dmmjobcontrol_pi1[search][keyword]';
		$markerArray['###FULLNAME_VALUE###'] = '';
		$markerArray['###EMAIL_VALUE###'] = '';

		$session = $GLOBALS['TSFE']->fe_user->getKey('ses', $this->prefixId);
		$markerArray['###KEYWORD_VALUE###'] = $session['search']['keyword'];

		// Logged in FE user?
		if ($GLOBALS["TSFE"]->loginUser) {
			$markerArray['###FULLNAME_VALUE###'] = $GLOBALS['TSFE']->fe_user->user['name'];
			$markerArray['###EMAIL_VALUE###'] = $GLOBALS['TSFE']->fe_user->user['email'];
		}

		// Extend the markerArray with user function?
		if (isset($this->conf['searchArrayFunction']) && $this->conf['searchArrayFunction']) {
			$funcConf = $this->conf['searchArrayFunction.'];
			$funcConf['parent'] = & $this;
			$markerArray = $this->cObj->callUserFunction($this->conf['searchArrayFunction'], $funcConf, $markerArray);
		}

		return $markerArray;
	}

	/**
	 * Make an apply form
	 *
	 * @param string $template The ###APPLY### part of the detail template, including INCLUDE_APPLY_FORM
	 * @param array $markerArray The marker array that will be extended
	 * @return string The content that will be inserted into the detail template
	 */
	function getApplyFormData($template, $markerArray) {
		// The javascript function to check the extension
		$GLOBALS['TSFE']->additionalJavaScript[] = '
			function checkExtension(obj) {
			var extension = obj.value.substr((obj.value.length-4), 4);
			if (!(extension=="docx" || extension==".doc" || extension==".pdf" || extension==".odt" || extension==".sxw" || extension==".rtf" || extension==".zip" || extension=="DOCX" || extension==".DOC" || extension==".PDF" || extension==".ODT" || extension==".SXW" || extension==".RTF" || extension==".ZIP")) {
					alert("'.$this->pi_getLL('wrong_document_type').'");
					obj.value = "";
				}
			}';

		$markerArray['###FORM_ATTRIBUTES###'] = ' enctype="multipart/form-data" action="'.$this->cObj->getTypoLink_URL($GLOBALS['TSFE']->id, array('tx_dmmjobcontrol_pi1[job_uid]' => $this->piVars['job_uid'])).'" method="post" ';
		if ($this->conf['apply.']['form'] == 1) {
			$markerArray['###FORM_ATTRIBUTES###'] .= 'style="display:none" ';
		}

		if (count($this->requiredFields)) {
			$markerArray['###FORM_ATTRIBUTES###'] .= 'onsubmit="return checkRequiredFields(this);" ';

			foreach ($this->requiredFields AS $requiredField) {
				$check[] = '
					if (obj.elements["tx_dmmjobcontrol_pi1[apply]['.$requiredField.']"].value == "") {
						errorMsg = errorMsg + "\n - '.$this->pi_getLL($requiredField.'_label').'";
						valid = false;
					}
				';
			}

			$GLOBALS['TSFE']->additionalJavaScript[] = '
				function checkRequiredFields(obj) {
					var valid = true;
					var errorMsg = "'.$this->pi_getLL('apply_missing_fields').'";
					'.implode('', $check).'
					if (valid == false) {
						alert(errorMsg);
					}
					return valid;
				}
			';
		}

		if ($this->conf['apply.']['form'] == 1) {
			$markerArray['###APPLY_LINK_ATTRIBUTES###'] = ' href="'.$markerArray['###LINKTODETAIL###'].'#dmmjobcontrol_apply_href" onclick="document.getElementById(\'dmmjobcontrol_apply_form\').style.display=\'block\'" ';
		} else {
			$markerArray['###APPLY_LINK_ATTRIBUTES###'] = ' href="'.$markerArray['###LINKTOAPPLY###'].'" ';
		}

		$spamBlockValue = md5(time());

		// Write the spamblock value into the session
		$session = $GLOBALS['TSFE']->fe_user->getKey('ses', $this->prefixId);
		$session['spamblock'][] = $spamBlockValue;
		$GLOBALS['TSFE']->fe_user->setKey('ses', $this->prefixId, $session);

		$markerArray['###FULLNAME_NAME###'] = 'tx_dmmjobcontrol_pi1[apply][fullname]';
		$markerArray['###EMAIL_NAME###'] = 'tx_dmmjobcontrol_pi1[apply][email]';
		$markerArray['###FULLNAME_VALUE###'] = '';
		$markerArray['###EMAIL_VALUE###'] = '';
		$markerArray['###MOTIVATION_NAME###'] = 'tx_dmmjobcontrol_pi1[apply][motivation]';
		$markerArray['###CV_NAME###'] = 'tx_dmmjobcontrol_pi1[apply][cv]';
		$markerArray['###LETTER_NAME###'] = 'tx_dmmjobcontrol_pi1[apply][letter]';
		$markerArray['###APPLY_NAME###'] = 'tx_dmmjobcontrol_pi1[apply_submit]';
		$markerArray['###JOB_UID_NAME###'] = 'tx_dmmjobcontrol_pi1[job_uid]';
		$markerArray['###JOB_UID_VALUE###'] = (int)$this->piVars['job_uid'];
		$markerArray['###JOB_UID###'] = $this->piVars['job_uid']; // backward compat.
		$markerArray['###SPAMBLOCK_NAME###'] = 'tx_dmmjobcontrol_pi1[sessioncheck]';
		$markerArray['###SPAMBLOCK_VALUE###'] = $spamBlockValue;

		// Logged in FE user?
		if ($GLOBALS["TSFE"]->loginUser) {
			$markerArray['###FULLNAME_VALUE###'] = $GLOBALS['TSFE']->fe_user->user['name'];
			$markerArray['###EMAIL_VALUE###'] = $GLOBALS['TSFE']->fe_user->user['email'];
		}

		return $this->cObj->substituteMarkerArrayCached($template, $markerArray);
	}

	/**
	 * Create a selectbox for a given field. The values come either from a seperate table or from the $TCA array.
	 *
	 * @param string $field	The field for which to create the selectbox
	 * @param string $select Can be either "single" or "multiple"
	 * @return string The html to be inserted into the form
	 */
	function getFormSelect($field) {
		global $TCA;

		$session = $GLOBALS['TSFE']->fe_user->getKey('ses', $this->prefixId);

		if (isset($this->conf['multipleselect.'][$field]) && $this->conf['multipleselect.'][$field] != 1) {
			$multiple = ' multiple="multiple" size="'.$this->conf['multipleselect.'][$field].'"';
		} else {
			$multiple = '';
		}
		
		$return = '<select name="tx_dmmjobcontrol_pi1[search]['.$field.'][]" class="dmmjobcontrol_select dmmjobcontrol_'.$field.'"'.$multiple.'>';
		if ($multiple == '') {
			$return .= '<option value="-1">'.$this->pi_getLL('form_select_text').'</option>';
		}

		if (isset($TCA['tx_dmmjobcontrol_job']['columns'][$field]['config']['MM'])) {
			// The values for the select come from another table
			$whereAdd = 'pid IN ('.implode(',', $this->sysfolders).')';
			$whereAddLang = ' AND sys_language_uid='.$GLOBALS['TSFE']->sys_language_content;

			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, name', $TCA['tx_dmmjobcontrol_job']['columns'][$field]['config']['foreign_table'], $whereAdd.$whereAddLang, '', 'name');
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$selected = '';
				if (isset($session['search'][$field]) && in_array($row['uid'], $session['search'][$field])) {
					$selected = ' selected="selected"';
				}

				$return .= '<option value="'.$row['uid'].'"'.$selected.'>'.$row['name'].'</option>';
			}
		} else {
			// The values are in $TCA
			foreach ($TCA['tx_dmmjobcontrol_job']['columns'][$field]['config']['items'] AS $row) {
				$selected = '';
				if (isset($session['search'][$field]) && in_array($row[1], $session['search'][$field])) {
					$selected = ' selected="selected"';
				}

				$return .= '<option value="'.$row[1].'"'.$selected.'>'.$GLOBALS['TSFE']->sL($row[0]).'</option>';
			}
		}

		$return .= '</select>';
		
		if ($this->conf['show_icon'] && $selected == ' selected="selected"') {
			$return .= '<img class="dmmjobcontrol_selected_icon" src="typo3conf/ext/'.$this->extKey.'/icon_tx_dmmjobcontrol_arrow.gif" alt="" title="selected">';
		}

		return $return;
	}

	/**
	 * Insert a database row containing one job, and return an array with markers ready to parse
	 *
	 * @param array $row The array containing the complete job
	 * @return array Array containing all the markers and their values
	 */
	function getJobData($row) {
		$markerArray = array(
			'###REFERENCE###' => $this->cObj->stdWrap($row['reference'], $this->conf['reference_stdWrap.']),
			'###JOB_TITLE###' => $this->cObj->stdWrap($row['job_title'], $this->conf['job_title_stdWrap.']),
			'###LOCATION###' => $this->cObj->stdWrap($row['location'], $this->conf['location_stdWrap.']),
			'###JOB_DESCRIPTION###' => $this->cObj->stdWrap($row['job_description'], $this->conf['job_description_stdWrap.']),
			'###JOB_REQUIREMENTS###' => $this->cObj->stdWrap($row['job_requirements'], $this->conf['job_requirements_stdWrap.']),
		);

		if (!$this->rssMode && !$this->conf['ignore_search']) {
			$session = $GLOBALS['TSFE']->fe_user->getKey('ses', $this->prefixId);
			if (isset($session['search']['keyword']) && $search = $session['search']['keyword']) {
				$keywords = str_replace(array(','), ' ', $search);
				$keywords = explode(' ', $keywords);

				foreach ($markerArray AS $key => $value) {
					$markerArray[$key] = preg_replace('/('.implode('|', $keywords).')/i', '<span class="dmmjobcontrol_highlight">$1</span>', $value);
				}
			}
		}

		$markerArray['###UID###'] = $this->cObj->stdWrap($row['uid'], $this->conf['uid_stdWrap.']);
		$markerArray['###CRDATE###'] = $this->cObj->stdWrap($row['crdate'], $this->conf['crdate_stdWrap.']);
		$markerArray['###EMPLOYER###'] = $this->cObj->stdWrap($row['employer'], $this->conf['employer_stdWrap.']);
		$markerArray['###EMPLOYER_DESCRIPTION###'] = $this->cObj->stdWrap($row['employer_description'], $this->conf['employer_description_stdWrap.']);
		$markerArray['###SHORT_JOB_DESCRIPTION###'] = $this->cObj->stdWrap($row['short_job_description'], $this->conf['short_job_description_stdWrap.']);
		$markerArray['###EXPERIENCE###'] = $this->cObj->stdWrap($row['experience'], $this->conf['experience_stdWrap.']);
		$markerArray['###JOB_BENEFITS###'] = $this->cObj->stdWrap($row['job_benefits'], $this->conf['job_benefits_stdWrap.']);
		$markerArray['###APPLY_INFORMATION###'] = $this->cObj->stdWrap($row['apply_information'], $this->conf['apply_information_stdWrap.']);
		$markerArray['###SALARY###'] = $this->cObj->stdWrap($row['salary'], $this->conf['salary_stdWrap.']);
		$markerArray['###LINKTODETAIL###'] = $GLOBALS['TSFE']->baseUrlWrap($this->cObj->getTypoLink_URL($this->conf['pid.']['detail'] ? $this->conf['pid.']['detail'] : $GLOBALS['TSFE']->id, array( 'tx_dmmjobcontrol_pi1[job_uid]' => $row['uid'] )));
		$markerArray['###LINKTOAPPLY###'] = $GLOBALS['TSFE']->baseUrlWrap($this->cObj->getTypoLink_URL($this->conf['pid.']['apply'] ? $this->conf['pid.']['apply'] : $GLOBALS['TSFE']->id, array( 'tx_dmmjobcontrol_pi1[job_uid]' => $row['uid'] )));
		$markerArray['###LINKTOLIST###'] = $GLOBALS['TSFE']->baseUrlWrap($this->cObj->getTypoLink_URL($this->conf['pid.']['list'] ? $this->conf['pid.']['list'] : $GLOBALS['TSFE']->id));
		$markerArray['###JOB_TYPE###'] = $this->cObj->stdWrap($GLOBALS['TSFE']->sL('LLL:EXT:dmmjobcontrol/locallang_db.xml:tx_dmmjobcontrol_job.job_type.I.'.$row['job_type']), $this->conf['job_type_stdWrap.']);
		$markerArray['###CONTRACT_TYPE###'] = $this->cObj->stdWrap($GLOBALS['TSFE']->sL('LLL:EXT:dmmjobcontrol/locallang_db.xml:tx_dmmjobcontrol_job.contract_type.I.'.$row['contract_type']), $this->conf['contract_type_stdWrap.']);

		// Extend the markerArray with user function?
		if (isset($this->conf['markerArrayFunction']) && $this->conf['markerArrayFunction']) {
			$funcConf = $this->conf['markerArrayFunction.'];
			$funcConf['parent'] = & $this;
			$funcConf['row'] = $row;
			$markerArray = $this->cObj->callUserFunction($this->conf['markerArrayFunction'], $funcConf, $markerArray);
		}

		// Get regions
		$resMM = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('tx_dmmjobcontrol_region.*', '', 'tx_dmmjobcontrol_job_region_mm', 'tx_dmmjobcontrol_region', 'AND tx_dmmjobcontrol_job_region_mm.uid_local='.$row['uid']);
		$array = array();
		while($rowMM = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resMM)) {
			$array[] = $rowMM['name'];
		}
		$markerArray['###REGION###'] = $this->cObj->stdWrap(implode(', ', $array), $this->conf['region_stdWrap.']);

		// Get sectors
		$resMM = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('tx_dmmjobcontrol_sector.*', '', 'tx_dmmjobcontrol_job_sector_mm', 'tx_dmmjobcontrol_sector', 'AND tx_dmmjobcontrol_job_sector_mm.uid_local='.$row['uid']);
		$array = array();
		while($rowMM = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resMM)) {
			$array[] = $rowMM['name'];
		}
		$markerArray['###SECTOR###'] = $this->cObj->stdWrap(implode(', ', $array), $this->conf['sector_stdWrap.']);

		// Get categories
		$resMM = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('tx_dmmjobcontrol_category.*', '', 'tx_dmmjobcontrol_job_category_mm', 'tx_dmmjobcontrol_category', 'AND tx_dmmjobcontrol_job_category_mm.uid_local='.$row['uid']);
		$array = array();
		while($rowMM = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resMM)) {
			$array[] = $rowMM['name'];
		}
		$markerArray['###CATEGORY###'] = $this->cObj->stdWrap(implode(', ', $array), $this->conf['category_stdWrap.']);

		// Get disciplines
		$resMM = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('tx_dmmjobcontrol_discipline.*', '', 'tx_dmmjobcontrol_job_discipline_mm', 'tx_dmmjobcontrol_discipline', 'AND tx_dmmjobcontrol_job_discipline_mm.uid_local='.$row['uid']);
		$array = array();
		while($rowMM = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resMM)) {
			$array[] = $rowMM['name'];
		}
		$markerArray['###DISCIPLINE###'] = $this->cObj->stdWrap(implode(', ', $array), $this->conf['discipline_stdWrap.']);

		// Get education levels
		$resMM = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('tx_dmmjobcontrol_education.*', '', 'tx_dmmjobcontrol_job_education_mm', 'tx_dmmjobcontrol_education', 'AND tx_dmmjobcontrol_job_education_mm.uid_local='.$row['uid']);
		$array = array();
		while($rowMM = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resMM)) {
			$array[] = $rowMM['name'];
		}
		$markerArray['###EDUCATION###'] = $this->cObj->stdWrap(implode(', ', $array), $this->conf['education_stdWrap.']);

		// Get contact info
		$resMM = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tx_dmmjobcontrol_contact.*', 'tx_dmmjobcontrol_contact', 'tx_dmmjobcontrol_contact.uid='.$row['contact']);
		if ($rowMM = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resMM)) {
			foreach (array('name','address','phone','email') as $f) {
				$markerArray['###'.strtoupper('contact_'.$f).'###'] = $this->cObj->stdWrap($rowMM[$f], $this->conf['contact_'.$f.'_stdWrap.']);
			}
		}

		return $markerArray;
	}

	/**
	 * Make a page browser for insertion into the list template
	 *
	 * @param string $template The ###PAGEBROWSER### part of the list template
	 * @return string The content that will be inserted into the list template
	 */
	function getPageBrowser($template, $tableAdd, $whereAdd) {
		// Count how many jobs there are in total
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'COUNT(DISTINCT tx_dmmjobcontrol_job.uid) AS total',
			implode(', ', $tableAdd),
			implode(' AND ', $whereAdd),
			'',
			'',
			''
		);
		
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		$totalJobs = $row['total'];

		// How many pages are there?
		$nrPages = ceil($totalJobs / $this->conf['limit']);

		// What page are we on now?
		if (!(isset($this->piVars['page']) && $this->piVars['page'])) {
			$this->piVars['page'] = 1;
		}

		// Get the parts out of the template
		$templatePart['pagebrowser_header'] = $this->pi_getLL('pagebrowser_header');
		$templatePart['link_prev'] = $this->cObj->getSubpart($template, '###LINK_PREV###');
		$templatePart['browse_links'] = $this->cObj->getSubpart($template, '###BROWSE_LINKS###');
		$templatePart['browse_link'] = $this->cObj->getSubpart($templatePart['browse_links'], '###BROWSE_LINK###');
		$templatePart['link_next'] = $this->cObj->getSubpart($template, '###LINK_NEXT###');

		// The header
		$subMarkerArray = array();
		$subMarkerArray['###PAGE_FROM###'] = (($this->piVars['page'] - 1) * $this->conf['limit']) + 1;
		$subMarkerArray['###PAGE_TO###'] = (($this->piVars['page'] * $this->conf['limit']) > $totalJobs) ? $totalJobs : ($this->piVars['page'] * $this->conf['limit']);
		$subMarkerArray['###TOTAL_JOBS###'] = $totalJobs;
		$markerArray['###PAGEBROWSER_HEADER###'] .= $this->cObj->substituteMarkerArrayCached($templatePart['pagebrowser_header'], $subMarkerArray);

		// Link to previous page
		if ($this->piVars['page'] != 1) {
			$subMarkerArray = array();
			$subMarkerArray['###LINK_PREV_HREF###'] = $this->cObj->getTypoLink_URL($this->conf['pid.']['list']?$this->conf['pid.']['list']:$GLOBALS['TSFE']->id, array('tx_dmmjobcontrol_pi1[page]' => ($this->piVars['page'] - 1)));
			$subMarkerArray['###LINK_PREV_TITLE###'] = $this->pi_getLL('link_prev');
			$wrappedMarkerArray['###LINK_PREV###'] = $this->cObj->substituteMarkerArrayCached($templatePart['link_prev'], $subMarkerArray);
		} else {
			$wrappedMarkerArray['###LINK_PREV###'] = '';
		}

		// Links to individual pages
		if ($nrPages > 1) {
			for ($i = 1; $i <= $nrPages; $i++) {
				if ($this->piVars['page'] == $i) {
					$wrappedMarkerArray['###BROWSE_LINKS###'] .= $i;
				} else {
					$subMarkerArray = array();
					$subMarkerArray['###BROWSE_LINK_HREF###'] = $this->cObj->getTypoLink_URL($this->conf['pid.']['list']?$this->conf['pid.']['list']:$GLOBALS['TSFE']->id, array('tx_dmmjobcontrol_pi1[page]' => $i));;
					$subMarkerArray['###BROWSE_LINK_TITLE###'] = $i;
					$wrappedMarkerArray['###BROWSE_LINKS###'] .= $this->cObj->substituteMarkerArrayCached($templatePart['browse_link'], $subMarkerArray);
				}
			}
		} else {
			$wrappedMarkerArray['###BROWSE_LINKS###'] .= '';
		}

		// Link to next page
		if ($this->piVars['page'] != $nrPages) {
			$subMarkerArray = array();
			$subMarkerArray['###LINK_NEXT_HREF###'] = $this->cObj->getTypoLink_URL($this->conf['pid.']['list']?$this->conf['pid.']['list']:$GLOBALS['TSFE']->id, array('tx_dmmjobcontrol_pi1[page]' => ($this->piVars['page'] + 1)));
			$subMarkerArray['###LINK_NEXT_TITLE###'] = $this->pi_getLL('link_next');
			$wrappedMarkerArray['###LINK_NEXT###'] = $this->cObj->substituteMarkerArrayCached($templatePart['link_next'], $subMarkerArray);
		} else {
			$wrappedMarkerArray['###LINK_NEXT###'] = '';
		}

		return $this->cObj->substituteMarkerArrayCached($template, $markerArray, $wrappedMarkerArray);
	}

	/**
	 * Hide empty fields in FE
	 *
	 * @param array $labels The label marker array
	 * @param array $values The array holding the job data marker array
	 * @return array The combined markerArray that will be inserted into the detail template
	 */
	function hideEmpty($labels, $values, $row) {
		$markerArray = array();

		// Relate labels and values
		$label_values = array(
			'CRDATE_LABEL',
			'REFERENCE_LABEL',
			'JOB_TITLE_LABEL',
			'EMPLOYER_LABEL',
			'EMPLOYER_DESCRIPTION_LABEL',
			'LOCATION_LABEL',
			'SHORT_JOB_DESCRIPTION_LABEL',
			'JOB_DESCRIPTION_LABEL',
			'EXPERIENCE_LABEL',
			'JOB_REQUIREMENTS_LABEL',
			'SALARY_LABEL',
			'JOB_TYPE_LABEL',
			'CONTRACT_TYPE_LABEL',
			'REGION_LABEL',
			'SECTOR_LABEL',
			'CATEGORY_LABEL',
			'DISCIPLINE_LABEL',
			'EDUCATION_LABEL',
			'JOB_BENEFITS_LABEL',
			'APPLY_INFORMATION_LABEL',
		);

		foreach ($labels AS $k => $v) {
			// replace the marker symbols
			$field = str_replace('#', '', $k);

			if (in_array($field, $label_values)) {
				// extract field name from label
				$fieldName_parts = explode('_', $field);
				array_pop($fieldName_parts);
				$fieldName = implode('_', $fieldName_parts);
				$rowFieldName = strtolower($fieldName);

				// If it is empty fill label with a space
				if (!strlen($row[$rowFieldName])) {
					$labels[$k] = ' ';
					$values['###'.$fieldName.'###'] = '';
				}
			}
		}

		$markerArray = $labels + $values;
		return $markerArray;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dmmjobcontrol/pi1/class.tx_dmmjobcontrol_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dmmjobcontrol/pi1/class.tx_dmmjobcontrol_pi1.php']);
}

?>
