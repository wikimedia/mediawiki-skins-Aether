<?php
/**
 * Aether

 * @todo document
 * @file
 * @ingroup Skins
 */

require_once __DIR__.'/vendor/autoload.php';
require_once 'JsonManifestNetworkStrategy.php';

use MediaWiki\MediaWikiServices;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * SkinTemplate class for Neverland skin
 * @ingroup Skins
 */
class SkinAether extends SkinTemplate {
    private $neverlandConfig;
    private $aetherConfig;

    public function __construct( $options = null ) {
        $this->aetherConfig = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'aether' );
        parent::__construct( $options );
    }

    /**
     * Initializes output page and sets up skin-specific parameters
     * @param $out OutputPage object to initialize
     */
    public function initPage( OutputPage $out ) {

        parent::initPage( $out );

        // Append CSS which includes IE only behavior fixes for hover support -
        // this is better than including this in a CSS fille since it doesn't
        // wait for the CSS file to load before fetching the HTC file.
        $min = $this->getRequest()->getFuzzyBool( 'debug' ) ? '' : '.min';
        $cache = new FilesystemAdapter();

        $cdn = 'https://cdn.kde.org';
        $cdnPathPrefix = 'aether-devel';

        $cdnManifest = $cdn . '/' . $cdnPathPrefix . '/version/manifest.json';

        $cdnCSSFiles = ['/version/bootstrap.css', '/version/aether-mediawiki.css', '/version/aether-sidebar.css'];
        $cdnJSFiles = ['/version/bootstrap.js'];

        // $cache->delete('cdnFiles' . str_replace('/', '', implode('', $cdnCSSFiles) . implode('', $cdnJSFiles)));
        ini_set('realpath_cache_size', 0);

        $cdnFiles = $cache->get('cdnFiles' . str_replace('/', '', implode('', $cdnCSSFiles) . implode('', $cdnJSFiles)), function (ItemInterface $item) use ($cdnManifest, $cdnPathPrefix, $cdnCSSFiles, $cdnJSFiles) {
            $item->expiresAfter(600);
            $fileContent = file_get_contents($cdnManifest."?e");
            $manifestData = json_decode($fileContent, true);

            $convertPaths = function($cdnCSSFile) use ($cdnPathPrefix, $manifestData)  {
                return $manifestData[$cdnPathPrefix . $cdnCSSFile];
            };
            return [
                'css' => array_map($convertPaths, $cdnCSSFiles),
                'js' => array_map($convertPaths, $cdnJSFiles),
            ];
        });

        foreach ($cdnFiles['css'] as $cssFile) {
            $out->addStyle($cdn . $cssFile, 'all');
        }
        foreach ($cdnFiles['js'] as $jsFile) {
            $out->addScriptFile($cdn . $jsFile);
        }
    }
}
