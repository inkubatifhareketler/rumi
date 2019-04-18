<?php

class FileGeneration
{
    private $dir;
    private $name;
    private $title;
    private $description;
    private $theme;
    private $remote_url = "";
    private $public_id='79982844-6a27-4b3b-b77f-419a79be0e10';
    private $primary_color='rgb(111, 135, 159)';
    private $text_color = 'rgb(63, 95, 127)';
    private $background_color = 'white';
    private $host = "";
    private $stream_host = "";

    public function __construct(
        $dir, $name, $title, $theme = "light",
        $public_id = null, 
        $primary_color = null, 
        $text_color = null,
        $background_color = null,
        $host = null,
        $stream_host = null, 
        $remote_url = "",
        $description = ""
    )
    {
        $this->dir = $dir;
        $this->name = $name;
        $this->title = $title;
        $this->description = $description;
        $this->theme = $theme;
        $this->remote_url = $remote_url;
        if(!is_null($public_id)) $this->public_id = $public_id;
        if(!is_null($primary_color)) $this->primary_color = $primary_color;
        if(!is_null($text_color)) $this->text_color = $text_color;
        if(!is_null($background_color)) $this->background_color = $background_color;
        if(!is_null($host)) $this->host = $host;
        if(!is_null($stream_host)) $this->stream_host = $stream_host;
    }

    public function generate(bool $regen = false)
    {
        $dir = $this->dir;
        $name = $this->name;
        $title = $this->title;
        $description = $this->description;
        $theme = $this->theme;
        $public_id = $this->public_id;
        $primary_color = $this->primary_color;
        $text_color = $this->text_color;
        $background_color = $this->background_color;
        $host = $this->host;
        $stream_host = $this->stream_host;

        $templates = new League\Plates\Engine($dir);

        /// defaults
        $goal = "generate";
        /// defaults

        $site = __DIR__. '/../dist/' . $name;
        if(!$regen) {
            if (file_exists($site)) {
                throw new \Exception("There is an existing folder in: ".$site);
            }
        }
        elseif (file_exists($site)) {
            unlink($site.".html");
            $this->cleanupDir($site);
        }
        if (! file_exists($site)) {
            mkdir($site);
        }
        $files = scandir($dir);
        foreach($files as $file)
        {
            if(
                $file!='.'&&$file!='..'
                &&$file!='template.php'
                &&strlen($file)>4&&substr($file, -4)=='.php'
            ) {
                $page = substr($file, 0, -4);
                $html = $page.'.html';
                file_put_contents(
                    $site.'/'.$html,
                    $templates->render($page, [
                        "goal"=>$goal,
                        "public_id"=>$public_id,
                        "brand"=>$title,
                        "about"=>$description,
                        "primaryColor"     => $primary_color,
                        "textColor"        => $text_color,
                        "backgroundColor"  => $background_color,
                        "host"             => $host,
                        "streamHost"       => $stream_host,
                        "theme"            => $theme,
                        "name"             => $name
                    ])
                );
            }
        }
        // homepage
        copy($site.'/home.html', $site.'.html');

        $init = include __DIR__ . '/../lib/init.php';
        $initOutput = $init($public_id, $primary_color, $text_color, $background_color, $host, $stream_host);

        $packer = new \Tholu\Packer\Packer($initOutput, 'Normal', true, false, true);
        $packed = $packer->pack();
        $packed = $initOutput;
        $jsFilePath = $site.'/'.'init.js';
        file_put_contents($jsFilePath, $packed);

        $remoteUrl = $this->remote_url;
        $remoteName = 'origin';

        if (! $regen) {
            // new generated site is always git repo
            $repo = \Cz\Git\GitRepository::init($site);
            chdir($site);
            
            exec("git config user.email 'business@groups-inc.com'");
            exec("git config user.name 'system'");
            $repo->addAllChanges();
            $repo->commit((string) time());
            if ($remoteUrl) {
                $repo->addRemote($remoteName, $remoteUrl);
                $repo->push($remoteName, ['master']);
            }
        } else {
            // old generated site might not be git repo
            if (is_dir("$site/.git")) {
                $repo = new \Cz\Git\GitRepository($site);
                chdir($site);
                if ($repo->hasChanges()) {
                    $repo->addAllChanges();
                    $repo->commit(time());
                }
                if ($remoteUrl) {
                    try {
                        $repo->addRemote($remoteName, $remoteUrl);
                    }
                    catch(\Exception $e) {
                        $repo->setRemoteUrl($remoteName, $remoteUrl);
                    }
                    $repo->push($remoteName, ['master']);
                }
            }
        }
    }

    // https://andy-carter.com/blog/recursively-remove-a-directory-in-php
    private function cleanupDir(string $path) {
        $files = glob($path . '/*'); // glob ignores all hidden files
       foreach ($files as $file) {
           is_dir($file) ? $this->cleanupDir($file) : unlink($file);
       }
        return;
   }
}
