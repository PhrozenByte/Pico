<?php

/**
 * Pico cache plugin
 *
 * @author  Daniel Rudolf
 * @link    http://picocms.org
 * @license http://opensource.org/licenses/MIT
 * @version 1.0
 */
class PhrozenBytePicoCache extends AbstractPicoPlugin
{
    /**
     * This plugin is disabled by default
     *
     * @var boolean
     * @see AbstractPicoPlugin::$enabled
     */
    protected $enabled = false;

    /**
     * Absolute path to the cached version of the page to serve
     *
     * @see PhrozenBytePicoCache::discoverCacheFile()
     * @var string|null
     */
    protected $cacheFile;

    protected $useCache;

    /**
     * Prepare the plugin configuration
     *
     * @see    DummyPlugin::onConfigLoaded()
     */
    public function onConfigLoaded(&$config)
    {
        $defaultConfig = array(
            'cache_dir' => null,
            'cache_expire' => 604800
        );

        if (!isset($config['PhrozenBytePicoCache'])) {
            $config['PhrozenBytePicoCache'] = $defaultConfig;
        } else {
            $config['PhrozenBytePicoCache'] += $defaultConfig;
        }

        $pluginConfig = &$config['PhrozenBytePicoCache'];
        if (!empty($pluginConfig['cache_dir'])) {
            $pluginConfig['cache_dir'] = $this->getAbsolutePath($pluginConfig['cache_dir']);
        }
    }

    /**
     * Triggered after Pico has discovered the content file to serve
     *
     * @see    Pico::getRequestFile()
     * @param  string &$file absolute path to the content file to serve
     * @return void
     */
    public function onRequestFile(&$file)
    {
        // check cache usage
        $this->useCache = false;
        if ($this->getConfig('cache_dir') && file_exists($file)) {
            $this->cacheFile = $this->discoverCacheFile($file);
            if (file_exists($this->cacheFile)) {
                $this->useCache = true;
                $this->triggerEvent('onCacheHit', array($file, &$this->cacheFile, &$useCache));

                if ($this->useCache) {
                    $cacheFileLastModTime = filemtime($this->cacheFile);
                    $requestFileLastModTime = filemtime($file);

                    $this->useCache = (
                        $cacheFileLastModTime && $requestFileLastModTime
                        && ($requestFileLastModTime < $cacheFileLastModTime)
                        && (time() < ($cacheFileLastModTime + $this->getConfig('cache_expire')))
                    );
                }
            }
        }

        // use cache to return contents
        if ($this->useCache) {
            // check If-Modified-Since header
            $cacheFileLastModTime = filemtime($this->cacheFile);
            if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
                $requestIfModifiedSince = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
                if ($requestIfModifiedSince >= $cacheFileLastModTime) {
                    header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified');
                    exit();
                }
            }

            // load cached output
            $this->triggerEvent('onCacheLoading', array($this->requestFile, &$this->cacheFile));

            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $cacheFileLastModTime) . ' GMT');
            $output = $this->loadCacheFile($this->cacheFile);

            $this->triggerEvent('onCacheLoaded', array(&$output));
        }
    }

    /**
     * Triggered before Pico reads the contents of the file to serve
     *
     * @see    Pico::loadFileContent()
     * @param  string &$file path to the file which contents will be read
     * @return void
     */
    public function onContentLoading(&$file)
    {
        // your code
    }

    /**
     * Triggered after Pico has read the contents of the file to serve
     *
     * @see    Pico::getRawContent()
     * @param  string &$rawContent raw file contents
     * @return void
     */
    public function onContentLoaded(&$rawContent)
    {
        // your code
    }

    /**
     * Triggered before Pico reads the contents of the 404 file
     *
     * @see    Pico::load404Content()
     * @param  string &$file path to the file which contents were requested
     * @return void
     */
    public function on404ContentLoading(&$file)
    {
        // your code
    }

    /**
     * Triggered after Pico has read the contents of the 404 file
     *
     * @see    Pico::getRawContent()
     * @param  string &$rawContent raw file contents
     * @return void
     */
    public function on404ContentLoaded(&$rawContent)
    {
        // your code
    }

    /**
     * Triggered when Pico reads its known meta header fields
     *
     * @see    Pico::getMetaHeaders()
     * @param  string[] &$headers list of known meta header
     *     fields; the array value specifies the YAML key to search for, the
     *     array key is later used to access the found value
     * @return void
     */
    public function onMetaHeaders(&$headers)
    {
        // your code
    }

    /**
     * Triggered before Pico parses the meta header
     *
     * @see    Pico::parseFileMeta()
     * @param  string   &$rawContent raw file contents
     * @param  string[] &$headers    known meta header fields
     * @return void
     */
    public function onMetaParsing(&$rawContent, &$headers)
    {
        // your code
    }

    /**
     * Triggered after Pico has parsed the meta header
     *
     * @see    Pico::getFileMeta()
     * @param  string[] &$meta parsed meta data
     * @return void
     */
    public function onMetaParsed(&$meta)
    {
        // your code
    }

    /**
     * Triggered before Pico parses the pages content
     *
     * @see    Pico::prepareFileContent()
     * @param  string &$rawContent raw file contents
     * @return void
     */
    public function onContentParsing(&$rawContent)
    {
        // your code
    }

    /**
     * Triggered after Pico has prepared the raw file contents for parsing
     *
     * @see    Pico::parseFileContent()
     * @param  string &$content prepared file contents for parsing
     * @return void
     */
    public function onContentPrepared(&$content)
    {
        // your code
    }

    /**
     * Triggered after Pico has parsed the contents of the file to serve
     *
     * @see    Pico::getFileContent()
     * @param  string &$content parsed contents
     * @return void
     */
    public function onContentParsed(&$content)
    {
        // your code
    }

    /**
     * Triggered when Pico reads a single page from the list of all known pages
     *
     * @param array &$pageData {
     *     data of the loaded page
     *
     *     @var string $id             relative path to the content file
     *     @var string $url            URL to the page
     *     @var string $title          title of the page (YAML header)
     *     @var string $description    description of the page (YAML header)
     *     @var string $author         author of the page (YAML header)
     *     @var string $time           timestamp derived from the Date header
     *     @var string $date           date of the page (YAML header)
     *     @var string $date_formatted formatted date of the page
     *     @var string $raw_content    raw, not yet parsed contents of the page
     *     @var string $meta           parsed meta data of the page
     * }
     * @return void
     */
    public function onSinglePageLoaded(&$pageData)
    {
        // your code
    }

    /**
     * Triggered after Pico has read all known pages
     *
     * See {@link DummyPlugin::onSinglePageLoaded()} for details about the
     * structure of the page data.
     *
     * @see    Pico::getPages()
     * @see    Pico::getCurrentPage()
     * @see    Pico::getPreviousPage()
     * @see    Pico::getNextPage()
     * @param  array &$pages        data of all known pages
     * @param  array &$currentPage  data of the page being served
     * @param  array &$previousPage data of the previous page
     * @param  array &$nextPage     data of the next page
     * @return void
     */
    public function onPagesLoaded(&$pages, &$currentPage, &$previousPage, &$nextPage)
    {
        // your code
    }

    /**
     * Triggered before Pico registers the twig template engine
     *
     * @return void
     */
    public function onTwigRegistration()
    {
        // your code
    }

    /**
     * Triggered before Pico renders the page
     *
     * @see    Pico::getTwig()
     * @param  Twig_Environment &$twig          twig template engine
     * @param  mixed[]          &$twigVariables template variables
     * @param  string           &$templateName  file name of the template
     * @return void
     */
    public function onPageRendering(&$twig, &$twigVariables, &$templateName)
    {
        // your code
    }

    /**
     * Triggered after Pico has rendered the page
     *
     * @param  string &$output contents which will be sent to the user
     * @return void
     */
    public function onPageRendered(&$output)
    {
        // your code
    }

    /**
     * Returns the absolute path to the cached version of a file
     *
     * This method just returns the supposed file path, it doesn't guarantee
     * that the cache hasn't expired yet or it even exists.
     *
     * @param  string $file file path
     * @return string       file path to the cached version
     */
    public function discoverCacheFile($file)
    {
        $id = substr($file, strlen($this->getConfig('content_dir')), -strlen($this->getConfig('content_ext')));
        return $this->getConfig('cache_dir') . $id . '.html';
    }

    /**
     * Returns the absolute path to the cached version of the page to serve
     *
     * @see    Pico::discoverCacheFile()
     * @return string|null file path
     */
    public function getCacheFile()
    {
        return $this->cacheFile;
    }

    /**
     * Creates a cache file
     *
     * @param  string $file    path to the cache file
     * @param  string $content designated content of the cache file
     * @return void
     */
    public function createCacheFile($file, $content)
    {
        file_put_contents($file, $content, LOCK_EX);
    }

    /**
     * Returns the contents of a cache file
     *
     * @param  string $file path to a cache file
     * @return string       contents of the cache
     */
    public function loadCacheFile($file)
    {
        return file_get_contents($file);
    }
}
