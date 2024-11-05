<?php

namespace TickTackk\DeveloperTools;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter as DbAlterSchema;
use XF\Util\File as FileUtil;
use XF\Util\Json as JsonUtil;
use XF\AddOn\AddOn;

use function array_key_exists, count, is_string;

/**
 * @since 1.0.0
 * @version 1.5.0
 */
class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    /**
     * @version 1.3.6
     */
    public function installStep1() : void
    {
        $sm = $this->schemaManager();

        $sm->createTable('xf_tck_developer_tools_email_log', function(\XF\Db\Schema\Create $table)
        {
            $table->addColumn('email_id', 'int')->nullable()->autoIncrement();
            $table->addColumn('subject', 'text');
            $table->addColumn('log_date', 'int');
            $table->addColumn('return_path', 'blob');
            $table->addColumn('sender', 'blob')->nullable();
            $table->addColumn('from', 'blob');
            $table->addColumn('reply_to', 'blob')->nullable();
            $table->addColumn('to', 'blob');
            $table->addColumn('cc', 'blob')->nullable();
            $table->addColumn('bcc', 'blob')->nullable();
            $table->addColumn('html_message', 'blob')->nullable();
            $table->addColumn('text_message', 'blob')->nullable();

            $table->addKey('log_date');
        });
    }

    public function upgrade1000033Step1() : void
    {
        $addOns = $this->db()->fetchAll("
    		SELECT * FROM xf_addon
    		WHERE (
    			devTools_license <> ''
    			OR devTools_gitignore <> ''
    			OR devTools_readme_md <> ''
    			OR devTools_parse_additional_files <> ''
    		)
    		  AND addon_id NOT IN('XF', 'XFRM', 'XFMG', 'XFES', 'XFI')
    	");

        if (count($addOns))
        {
            foreach ($addOns AS $addOn)
            {
                $addOnEntity = \XF::em()->find('XF:AddOn', $addOn['addon_id']);
                $addOn = new AddOn($addOnEntity, \XF::app()->addOnManager());

                $addOnDir = $addOn->getAddOnDirectory();
                FileUtil::writeFile($addOnDir . DIRECTORY_SEPARATOR . 'dev.json', JsonUtil::jsonEncodePretty([
                    'gitignore' => $addOn['devTools_gitignore'],
                    'license' => $addOn['devTools_license'],
                    'readme' => $addOn['devTools_readme_md'],
                    'parse_additional_files' => (bool) $addOn['devTools_parse_additional_files']
                ]), false);
            }
        }
    }

    /**
     * @version 1.3.6
     */
    public function upgrade1000033Step2() : void
    {
        $this->schemaManager()->alterTable('xf_addon', function (DbAlterSchema $table)
        {
            $table->dropColumns(['devTools_license', 'devTools_gitignore', 'devTools_readme_md', 'devTools_parse_additional_files']);
        });
    }

    public function upgrade1010070Step1() : void
    {
        $db = $this->db();

        $addOns = $db->fetchAllColumn("
            SELECT addon_id
            FROM xf_addon
            WHERE addon_id NOT IN('XF', 'XFRM', 'XFMG', 'XFI', 'XFES')
        ");

        foreach ($addOns AS $addOnId)
        {
            $addOnEntity = \XF::em()->find('XF:AddOn', $addOnId);

            $addOnManager = $this->app()->addOnManager();
            $addOn = new AddOn($addOnEntity, $addOnManager);

            $addOnDir = $addOn->getAddOnDirectory();

            $gitJsonPath = FileUtil::canonicalizePath('git.json', $addOnDir);
            if (\file_exists($gitJsonPath))
            {
                \unlink($gitJsonPath);
            }

            $noUploadsDir = FileUtil::canonicalizePath('_no_upload', $addOnDir);
            $devJsonPath = FileUtil::canonicalizePath('dev.json', $addOnDir);
            if (\file_exists($devJsonPath))
            {
                $dev = \json_decode(\file_get_contents($devJsonPath), true);

                if (array_key_exists('parse_additional_files', $dev))
                {
                    unset($dev['parse_additional_files']);
                }

                $markdownFiles = [
                    'readme' => ['README', 'README.md'],
                    'license' => ['LICENSE', 'LICENSE.md'],
                ];

                foreach ($markdownFiles AS $key => $details)
                {
                    if (array_key_exists($key, $dev))
                    {
                        $fileNameWithoutExtension = $dev[0];
                        $preferredFileName = $dev[1];
                        $fileContents = $dev[$key];
                        $createFile = true;

                        foreach (['.md', '', '.txt', '.html'] AS $extension)
                        {
                            $possibleFilePathInAddOnRoot = FileUtil::canonicalizePath($fileNameWithoutExtension . $extension, $addOnDir);
                            $fileDateInAddOnRootDir = 0;
                            if (\file_exists($possibleFilePathInAddOnRoot))
                            {
                                $createFile = false;
                                $fileDateInAddOnRootDir = \filemtime($possibleFilePathInAddOnRoot);
                            }

                            $possibleFilePathInNoUploads = FileUtil::canonicalizePath($fileNameWithoutExtension . $extension, $noUploadsDir);
                            $fileDateInNoUploadsDir = 0;
                            if (\file_exists($possibleFilePathInNoUploads))
                            {
                                $createFile = false;
                                $fileDateInNoUploadsDir = \filemtime($possibleFilePathInNoUploads);
                            }

                            $copyToAddOnRootWithPreferredFileName = function (string $oldFilePath) use($addOnDir, $preferredFileName)
                            {
                                $newDestination = FileUtil::canonicalizePath($preferredFileName, $addOnDir);
                                FileUtil::copyFile($oldFilePath, $newDestination);
                            };

                            if ($fileDateInAddOnRootDir >= $fileDateInNoUploadsDir)
                            {
                                $copyToAddOnRootWithPreferredFileName($possibleFilePathInAddOnRoot);

                                if ($fileDateInNoUploadsDir !== 0)
                                {
                                    \unlink($possibleFilePathInNoUploads);
                                }
                            }
                            else if ($fileDateInAddOnRootDir <= $fileDateInNoUploadsDir)
                            {
                                $copyToAddOnRootWithPreferredFileName($possibleFilePathInNoUploads);

                                if ($fileDateInAddOnRootDir !== 0)
                                {
                                    \unlink($possibleFilePathInAddOnRoot);
                                }
                            }
                        }

                        if ($createFile)
                        {
                            if (is_string($fileContents) && !empty($fileContents))
                            {
                                $markdownPath = FileUtil::canonicalizePath($preferredFileName, $addOnDir);
                                FileUtil::writeFile($markdownPath, $fileContents, false);
                            }
                        }

                        unset($dev[$key]);
                    }
                }

                if (array_key_exists('gitignore', $dev))
                {
                    $finalGitIgnore = preg_split('/\r?\n/', $dev['gitignore'], \PREG_SPLIT_NO_EMPTY);
                    \array_push($finalGitIgnore, ['_releases', '/.idea/', '_build', '_vendor', '.DS_Store', 'hashes.json', '.phpstorm.meta.php', '_metadata.json', '.php_cs.cache']);

                    if (count($finalGitIgnore))
                    {
                        $gitIgnoreFileInAddOnRoot = FileUtil::canonicalizePath('.gitignore', $addOnDir);
                        if (\file_exists($gitIgnoreFileInAddOnRoot))
                        {
                            $gitIgnoreFileContentsFromAddOnRoot = preg_split('/\r?\n/', \file_get_contents($gitIgnoreFileInAddOnRoot), \PREG_SPLIT_NO_EMPTY);
                            if (count($gitIgnoreFileContentsFromAddOnRoot))
                            {
                                \array_push($finalGitIgnore, ...$gitIgnoreFileContentsFromAddOnRoot);
                            }
                        }

                        $finalGitIgnore = \array_unique($finalGitIgnore);
                        \sort($finalGitIgnore);

                        FileUtil::writeFile($gitIgnoreFileInAddOnRoot, \implode(PHP_EOL, $finalGitIgnore));
                    }

                    unset($dev['gitignore']);
                }

                if (empty($dev))
                {
                    \unlink($devJsonPath);
                }
            }

            if (\is_dir($noUploadsDir))
            {
                $noUploadsFSIterator = new \FilesystemIterator($noUploadsDir);
                if (\iterator_count($noUploadsFSIterator) === 0)
                {
                    FileUtil::deleteDirectory($noUploadsDir);
                }
            }
        }
    }

    public function upgrade1030070Step1() : void
    {
        $this->installStep1();
    }

    /**
     * @since 1.3.6
     */
    public function upgrade1030670Step1() : void
    {
        $sm = $this->schemaManager();

        $sm->alterTable('xf_tck_developer_tools_email_log', function(DbAlterSchema $table)
        {
            $table->addColumn('html_message', 'blob')->nullable();
            $table->addColumn('text_message', 'blob')->nullable();
        });
    }

    /**
     * @since 1.3.7
     */
    public function upgrade1030770Step1() : void
    {
        $development = $this->app->config('development');
        $closureJarPath = $development['closureJar'] ?? null; // from add-on
        $closureCompilerPath = $development['closureCompilerPath'] ?? null; // from xf

        // We have add-on specific closure jar path set? If so, apply it to the XF 2.2.7+ config value
        if ($closureJarPath && !$closureCompilerPath)
        {
            $configLine = '$config[\'development\'][\'closureCompilerPath\'] = \'' . $closureJarPath . '\';';

            \file_put_contents(
                $this->app->container('config.file'),
                \PHP_EOL . $configLine,
                \FILE_APPEND
            );
        }
    }

    /**
     * @since 1.5.0
     *
     * @return void
     */
    public function upgrade1050070Step1() : void
    {
        $sm = $this->schemaManager();

        $sm->alterTable('xf_tck_developer_tools_email_log', function(DbAlterSchema $table)
        {
            $table->changeColumn('return_path', 'blob');
        });
    }

    /**
     * @since 1.5.0
     *
     * @param array $stepParams
     *
     * @return array|bool
     */
    public function upgrade1050070Step2(array $stepParams)
    {
        $stepParams = array_replace([
            'position' => 0
        ],  $stepParams);

        $db = $this->db();

        $emailIds = $db->fetchAllColumn($db->limit('
			SELECT email_id
			FROM xf_tck_developer_tools_email_log
			WHERE email_id > ?
			ORDER BY email_id
		', 10), $stepParams['position']);
        if (!$emailIds)
        {
            return null;
        }

        $db->beginTransaction();

        foreach ($emailIds AS $emailId)
        {
            $stepParams['position'] = $emailId;

            $returnPath = $db->fetchOne('
				SELECT return_path
				FROM xf_tck_developer_tools_email_log
				WHERE email_id = ?
			', $emailId);
            if (!$returnPath)
            {
                continue;
            }

            $db->update('xf_tck_developer_tools_email_log', [
                'return_path' => json_encode([$returnPath => null]),
            ], 'email_id = ?', $emailId);
        }

        $db->commit();

        return $stepParams;
    }
}
