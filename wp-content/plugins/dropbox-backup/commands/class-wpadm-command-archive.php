<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPadm_Command_Archive extends WPAdm_Сommand{
    public function execute(WPAdm_Command_Context $context)
    {
        if ( WPAdm_Running::is_stop() ) {
            require_once WPAdm_Core::getPluginDir() . '/modules/class-wpadm-archive.php';
            $af = $this->getArchiveName($context->get('to_file'));
            if ( WPAdm_Running::is_stop() ) {
                $archive = new WPAdm_Archive($af, $context->get('to_file') . '.md5');
                $archive->setRemovePath($context->get('remove_path'));
                $files = $context->get('files'); 
            } else {
                return true;
            }
            if ( !file_exists( $af ) ) {
                WPAdm_Core::log(__('Create part ','dropbox-backup') . basename( $af ) );
            }
            if (file_exists($af) && filesize($af) > $context->get('max_file_size')) {
                if ( WPAdm_Running::is_stop() ) {
                    $af = $this->getNextArchiveName($context->get('to_file'));
                    unset($archive);
                    if ( !file_exists( $af ) ) {
                        WPAdm_Core::log(__('Create part ','dropbox-backup') . basename( $af ) );
                    }
                    $archive = new WPAdm_Archive($af, $context->get('to_file') . '.md5');
                    $archive->setRemovePath($context->get('remove_path'));
                } else {
                    return true;
                }
            }
            if ( WPAdm_Running::is_stop() ) {
				$md5 = md5( print_r( $files, 1 ) );
				$files_str = implode(',', $files);
                $files_archive = WPAdm_Running::getCommandResultData('archive');
                if ( WPAdm_Running::is_stop() ) {
					if (!in_array($md5, $files_archive)) {
                        if ( WPAdm_Running::is_stop() ) {
                            $res = $archive->add($files_str);
                            if ($res) {
                                $files_archive = WPAdm_Running::getCommandResultData('archive');
                                $files_archive[] = $md5;
                                if (!empty($files_archive)) {
                                    WPAdm_Running::setCommandResultData('archive', $files_archive);
                                    WPAdm_Process::setInc( 'archiving', count($files) );
                                }
                            } else {
                                $context->setError( $archive->error );
                                return false;
                            }
                        } else {
                            return true;
                        }
                    }
                } else {
                    return true;
                }
            } else {
                return true;
            }
        }
        return true;

    }

    private function getArchiveName($name)
    {
		//WPAdm_Core::log("{$name}-*.zip");
        $archives = glob("{$name}-*.zip");
		//WPAdm_Core::log( print_r($archives, 1) );
        if (empty($archives)) {
            return "{$name}-1.zip";
        }
        $n = count($archives);
        $f = "{$name}-{$n}.zip";
        return $f;
    }

    private function getNextArchiveName($name)
    {
		//WPAdm_Core::log("{$name}-*.zip");
        $archives = glob("{$name}-*.zip");
		$n = 1 + count($archives);
        $a = "{$name}-{$n}.zip";
		//WPAdm_Core::log($a);
        return $a;
    }
}