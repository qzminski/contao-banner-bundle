<?php

/**
 * Contao Open Source CMS, Copyright (C) 2005-2013 Leo Feyer
*
* Module ModuleBannerStatistics - Backend
* Backend statistics
*
* @copyright  Glen Langer 2015 <http://contao.ninja>
* @author     Glen Langer (BugBuster)
* @package    BannerStatistics
* @license    LGPL
* @filesource
* @see        https://github.com/BugBuster1701/banner
*/

/**
 * Run in a custom namespace, so the class can be replaced
*/
namespace BugBuster\BannerStatistics;

use BugBuster\BannerStatistics\BannerStatisticsHelper;
use BugBuster\Banner\BannerImage;
use BugBuster\Banner\BannerHelper;
use BugBuster\Banner\BannerLog;

/**
 * Class ModuleBannerStatistics
 *
 * @copyright  Glen Langer 2015 <http://contao.ninja>
 * @author     Glen Langer (BugBuster)
 * @package    BannerStatistics
 */
class ModuleBannerStatistics extends BannerStatisticsHelper
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_banner_stat';
    
    /**
     * Kat ID
     * @var int
     */
    protected $intCatID;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        \System::loadLanguageFile('tl_banner_stat'); 
        
        if ( (int)\Input::get('id') == 0)
        {
            $this->intCatID = (int)\Input::post('id'); //id for redirect on banner reset, category reset
        }
        else
        {
            $this->intCatID = (int)\Input::get('id'); //directly category link
        }
         
        if (\Input::post('act',true)=='zero') //action banner reset, category reset
        {
            $this->setZero();
        }
        //Get Debug Settings
        $objBannerHelper = new BannerHelper();
        $objBannerHelper->setDebugSettings($this->intCatID);
    }
    
    /**
     * Generate module
     */
    protected function compile()
    {
        $arrBanners      = array();
        $arrBannersStat  = array();
        $intCatIdAllowed = false;

        //alle Kategorien holen die der User sehen darf
        $arrBannerCategories = $this->getBannerCategoriesByUsergroups();
        // no categories : array('id' => '0', 'title' => '---------');
        // empty array   : array('id' => '0', 'title' => '---------');
        // array[0..n]   : array(0, array('id' => '1', ....), 1, ....)
       
        if ($this->intCatID == 0) //direkter Aufruf ohne ID
        {
            $this->intCatID = $this->getCatIdByCategories($arrBannerCategories);
        }
        else 
        {
            // ID des Aufrufes erlaubt?
            foreach ($arrBannerCategories as $value)
            {
                if ($this->intCatID == $value['id'])
                {
                    $intCatIdAllowed = true;
                }
            }
            if ( false === $intCatIdAllowed ) 
            {
            	$this->intCatID = $this->getCatIdByCategories($arrBannerCategories);
            }
            
        }
        $arrBanners = $this->getBannersByCatID($this->intCatID);
        $number_active   = 0;
        $number_inactive = 0;
        foreach ($arrBanners as $Banner) 
        {
            // Aufteilen nach intern, extern, text Banner
            switch ($Banner['banner_type'])
            {
                case self::BANNER_TYPE_INTERN :
                    //generate data
                    $arrBannersStat[] = $this->addBannerIntern($Banner);
                    break;
                case self::BANNER_TYPE_EXTERN :
                    //generate data
                    $arrBannersStat[] = $this->addBannerExtern($Banner);
                    break;
                case self::BANNER_TYPE_TEXT :
                    //generate data
                    $arrBannersStat[] = $this->addBannerText($Banner);
                    break;
            }
            //Gesamt Aktiv / Inaktiv zählen
            if ($Banner['banner_published_class'] == 'published') 
            {
                $number_active++;
            }
            else 
            {
                $number_inactive++;
            }
            //Gesamt Views / Klicks zählen
            $number_clicks += (int)$Banner['banner_clicks']; 
            $number_views  += (int)$Banner['banner_views'];
        }
        
        $this->Template->bannersstat      = $arrBannersStat;
        $this->Template->number_active    = $number_active;
        $this->Template->number_inactive  = $number_inactive;
        $this->Template->number_clicks    = $number_clicks;
        $this->Template->number_views     = $number_views;
        $this->Template->header_id        = $GLOBALS['TL_LANG']['tl_banner_stat']['id'];
        $this->Template->header_picture   = $GLOBALS['TL_LANG']['tl_banner_stat']['picture'];
        $this->Template->header_name      = $GLOBALS['TL_LANG']['tl_banner_stat']['name'];
        $this->Template->header_url       = $GLOBALS['TL_LANG']['tl_banner_stat']['URL'];
        $this->Template->header_active    = $GLOBALS['TL_LANG']['tl_banner_stat']['active'];
        $this->Template->header_prio      = $GLOBALS['TL_LANG']['tl_banner_stat']['Prio'];
        $this->Template->header_clicks    = $GLOBALS['TL_LANG']['tl_banner_stat']['clicks'];
        $this->Template->header_views     = $GLOBALS['TL_LANG']['tl_banner_stat']['views'];
        $this->Template->banner_version   = $GLOBALS['TL_LANG']['tl_banner_stat']['modname'] . ' ' . BANNER_VERSION .'.'. BANNER_BUILD;
        $this->Template->banner_footer    = $GLOBALS['TL_LANG']['tl_banner_stat']['comment'];
        $this->Template->banner_base      = \Environment::get('base');
        $this->Template->banner_base_be   = \Environment::get('base') . 'contao';
        $this->Template->theme            = $this->getTheme();
        $this->Template->theme0           = 'default';
        
        $this->Template->bannercats    = $arrBannerCategories;
        $this->Template->bannercatid   = $this->intCatID;
        $this->Template->bannerstatcat = $GLOBALS['TL_LANG']['tl_banner_stat']['kat'];
        $this->Template->exportfield   = $GLOBALS['TL_LANG']['tl_banner_stat']['kat'].' '.$GLOBALS['TL_LANG']['tl_banner_stat']['export'];
        $this->Template->bannercatzero        = $GLOBALS['TL_LANG']['tl_banner_stat']['cat_zero'];
        $this->Template->bannercatzerobutton  = $GLOBALS['TL_LANG']['tl_banner_stat']['cat_zero_button'];
        $this->Template->bannercatzerotext    = $GLOBALS['TL_LANG']['tl_banner_stat']['cat_zero_text'];
        $this->Template->bannercatzeroconfirm = $GLOBALS['TL_LANG']['tl_banner_stat']['cat_zero_confirm'];
        $this->Template->bannerclickthroughrate     = $GLOBALS['TL_LANG']['tl_banner_stat']['click_through_rate'];
        $this->Template->bannernumberactiveinactive = $GLOBALS['TL_LANG']['tl_banner_stat']['number_active_inactive'];
        $this->Template->bannernumberviewsclicks    = $GLOBALS['TL_LANG']['tl_banner_stat']['number_views_clicks'];
   
        $this->Template->banner_hook_panels = $this->addStatisticPanelLineHook();
        
    } // compile
    
    /**
     * Add textbanner
     *  
     * @param referenz    $Banner
     * @return array      $arrBannersStat
     */
    protected function addBannerText( &$Banner )
    {
        $arrBannersStat = array();
        // Kurz URL (nur Domain)
        $this->setBannerURL($Banner);
        BannerLog::writeLog(__METHOD__ , __LINE__ , 'banner_url: ' . $Banner['banner_url']);
        $Banner['banner_url'] = BannerHelper::decodePunycode($Banner['banner_url']); // #79 
        BannerLog::writeLog(__METHOD__ , __LINE__ , 'banner_url punydecode: ' . $Banner['banner_url']);
        $treffer = parse_url($Banner['banner_url']);
        $banner_url_kurz = $treffer['host'];
        if (isset($treffer['port']))
        {
            $banner_url_kurz .= ':'.$treffer['port'];
        }
        $MaxViewsClicks = $this->getMaxViewsClicksStatus($Banner);
        $this->setBannerPublishedActive($Banner);
        
        $arrBannersStat['banner_id'       ]    = $Banner['id'];
        $arrBannersStat['banner_name'     ]    = specialchars(ampersand($Banner['banner_name']));
        $arrBannersStat['banner_comment'  ]    = nl2br($Banner['banner_comment']);
        $arrBannersStat['banner_url_kurz' ]    = $banner_url_kurz;
        $arrBannersStat['banner_url'      ]    = (strlen($Banner['banner_url']) <61 ? $Banner['banner_url'] : substr($Banner['banner_url'], 0, 28)."[...]".substr($Banner['banner_url'],-24,24) );
        $arrBannersStat['banner_prio'     ]    = $GLOBALS['TL_LANG']['tl_banner_stat']['prio'][$Banner['banner_weighting']];
        $arrBannersStat['banner_views'    ]    = ($MaxViewsClicks[0]) ? $Banner['banner_views']  .'<br />'.$GLOBALS['TL_LANG']['tl_banner_stat']['max_yes'] : $Banner['banner_views'];
        $arrBannersStat['banner_clicks'   ]    = ($MaxViewsClicks[1]) ? $Banner['banner_clicks'] .'<br />'.$GLOBALS['TL_LANG']['tl_banner_stat']['max_yes'] : $Banner['banner_clicks'];
        $arrBannersStat['banner_active'   ]    = $Banner['banner_active'];
        $arrBannersStat['banner_pub_class']    = $Banner['banner_published_class'];
        $arrBannersStat['banner_zero'     ]    = $GLOBALS['TL_LANG']['tl_banner_stat']['zero_text'];
        $arrBannersStat['banner_confirm'  ]    = $GLOBALS['TL_LANG']['tl_banner_stat']['zero_confirm'];
        $arrBannersStat['banner_pic'      ]    = false; // Es ist kein Bild
        $arrBannersStat['banner_flash'    ]    = false;
        $arrBannersStat['banner_text'     ]    = true;   // Es ist ein Textbanner
        
        return $arrBannersStat;
    }
    
    /**
     * Add internal banner
     *
     * @param referenz    $Banner
     * @return array      $arrBannersStat
     */
    protected function addBannerIntern( &$Banner )
    {
        $arrBannersStat = array();
        $oriSize = false;
        
        // return array(bool $intMaxViews, bool $intMaxClicks)
        $MaxViewsClicks = $this->getMaxViewsClicksStatus($Banner);

        // set $Banner['banner_active'] as HTML Text
        // and $Banner['banner_published_class'] published/unpublished
        $this->setBannerPublishedActive($Banner);
        $this->setBannerURL($Banner);
        $Banner['banner_url'] = BannerHelper::decodePunycode($Banner['banner_url']); // #79
        
        //Pfad+Dateiname holen ueber UUID (findByPk leitet um auf findByUuid)
        $objFile = \FilesModel::findByPk($Banner['banner_image']);
        //BannerImage Class
        $this->BannerImage = new BannerImage();
        
        //Banner Art und Größe bestimmen
        $arrImageSize = $this->BannerImage->getBannerImageSize($objFile->path, self::BANNER_TYPE_INTERN);
        // 1 = GIF, 2 = JPG, 3 = PNG
        // 4 = SWF, 13 = SWC (zip-like swf file)
        // 5 = PSD, 6 = BMP, 7 = TIFF(intel byte order), 8 = TIFF(motorola byte order)
        // 9 = JPC, 10 = JP2, 11 = JPX, 12 = JB2, 13 = SWC, 14 = IFF
        switch ($arrImageSize[2])
        {
            case 1:  // GIF
            case 2:  // JPG
            case 3:  // PNG
                //Check ob Banner zu groß für Anzeige, @return array $Width,$Height,$oriSize     
                $arrNewBannerImageSize = $this->BannerImage->getCheckBannerImageSize($arrImageSize, 250, 200);
                break;
            default:
                break;
        }
        $intWidth  = $arrNewBannerImageSize[0];
        $intHeight = $arrNewBannerImageSize[1];
        $oriSize   = $arrNewBannerImageSize[2];
        
        switch ($arrImageSize[2])
        {
            case 1: // GIF
            case 2: // JPG
            case 3: // PNG
                if ($oriSize || $arrImageSize[2] == 1) // GIF 
                {
                    $Banner['banner_image'] = $this->urlEncode($objFile->path);
                }
                else
                {
                    $Banner['banner_image'] = \Image::get($this->urlEncode($objFile->path), $intWidth, $intHeight,'proportional');
                }
                $arrBannersStat['banner_id'       ]   = $Banner['id'];
                $arrBannersStat['banner_style'    ]   = '';
                $arrBannersStat['banner_name'     ]   = specialchars(ampersand($Banner['banner_name']));
                $arrBannersStat['banner_alt'      ]   = specialchars(ampersand($Banner['banner_name']));
                $arrBannersStat['banner_title'    ]   = $Banner['banner_url'];
                $arrBannersStat['banner_url'      ]   = (strlen($Banner['banner_url']) <61 ? $Banner['banner_url'] : substr($Banner['banner_url'], 0, 28)."[...]".substr($Banner['banner_url'],-24,24) );
                $arrBannersStat['banner_image'    ]   = $Banner['banner_image'];
                $arrBannersStat['banner_width'    ]   = $intWidth;
                $arrBannersStat['banner_height'   ]   = $intHeight;
                $arrBannersStat['banner_prio'     ]   = $GLOBALS['TL_LANG']['tl_banner_stat']['prio'][$Banner['banner_weighting']];
                $arrBannersStat['banner_views'    ]   = ($MaxViewsClicks[0]) ? $Banner['banner_views']  .'<br>'.$GLOBALS['TL_LANG']['tl_banner_stat']['max_yes'] : $Banner['banner_views'];
                $arrBannersStat['banner_clicks'   ]   = ($MaxViewsClicks[1]) ? $Banner['banner_clicks'] .'<br>'.$GLOBALS['TL_LANG']['tl_banner_stat']['max_yes'] : $Banner['banner_clicks'];
                $arrBannersStat['banner_active'   ]   = $Banner['banner_active'];
                $arrBannersStat['banner_pub_class']   = $Banner['banner_published_class'];
                $arrBannersStat['banner_zero'     ]   = $GLOBALS['TL_LANG']['tl_banner_stat']['zero_text'];
                $arrBannersStat['banner_confirm'  ]   = $GLOBALS['TL_LANG']['tl_banner_stat']['zero_confirm'];
                $arrBannersStat['banner_pic'      ]   = true; // Es ist ein Bild
                $arrBannersStat['banner_flash'    ]   = false;
                $arrBannersStat['banner_text'     ]   = false;
                break;
            default:
                $Banner['banner_image'] = $this->urlEncode($objFile->path);
                
                $arrBannersStat['banner_pic'     ]    = true; 
                $arrBannersStat['banner_flash'   ]    = false;
                $arrBannersStat['banner_text'    ]    = false;
                $arrBannersStat['banner_prio'    ]    = $GLOBALS['TL_LANG']['tl_banner_stat']['prio'][$Banner['banner_weighting']];
                $arrBannersStat['banner_views'   ]    = ($MaxViewsClicks[0]) ? $Banner['banner_views']  .'<br>'.$GLOBALS['TL_LANG']['tl_banner_stat']['max_yes'] : $Banner['banner_views'];
                $arrBannersStat['banner_clicks'  ]    = ($MaxViewsClicks[1]) ? $Banner['banner_clicks'] .'<br>'.$GLOBALS['TL_LANG']['tl_banner_stat']['max_yes'] : $Banner['banner_clicks'];
                $arrBannersStat['banner_active'  ]    = $Banner['banner_active'];
                $arrBannersStat['banner_style'   ]    = 'color:red;font-weight:bold;';
                $arrBannersStat['banner_alt'     ]    = $GLOBALS['TL_LANG']['tl_banner_stat']['read_error'];
                $arrBannersStat['banner_url'     ]    = $Banner['banner_image'];
                break;
        } // switch
        return $arrBannersStat;
    } //addBannerIntern
    
    
    protected function addBannerExtern( &$Banner )
    {
        $arrBannersStat = array();
        $oriSize = false;
        
        // return array(bool $intMaxViews, bool $intMaxClicks)
        $MaxViewsClicks = $this->getMaxViewsClicksStatus($Banner);
        
        // set $Banner['banner_active'] as HTML Text
        // and $Banner['banner_published_class'] published/unpublished
        $this->setBannerPublishedActive($Banner);
        $this->setBannerURL($Banner);
        $Banner['banner_url']   = BannerHelper::decodePunycode($Banner['banner_url']);
        
        //BannerImage Class
        $this->BannerImage = new BannerImage();
        
        //Banner Art und Größe bestimmen
        $arrImageSize = $this->BannerImage->getBannerImageSize($Banner['banner_image_extern'], self::BANNER_TYPE_EXTERN);
        // 1 = GIF, 2 = JPG, 3 = PNG
        // 4 = SWF, 13 = SWC (zip-like swf file)
        // 5 = PSD, 6 = BMP, 7 = TIFF(intel byte order), 8 = TIFF(motorola byte order)
        // 9 = JPC, 10 = JP2, 11 = JPX, 12 = JB2, 13 = SWC, 14 = IFF
        switch ($arrImageSize[2])
        {
            case 1:  // GIF
            case 2:  // JPG
            case 3:  // PNG
                //Check ob Banner zu groß für Anzeige, @return array $Width,$Height,$oriSize
                $arrNewBannerImageSize = $this->BannerImage->getCheckBannerImageSize($arrImageSize, 250, 200);
                break;
            default:
                break;
        }
        $intWidth  = $arrNewBannerImageSize[0];
        $intHeight = $arrNewBannerImageSize[1];
        $oriSize   = $arrNewBannerImageSize[2];
        unset($oriSize);
        
        switch ($arrImageSize[2])
        {
            case 1: // GIF
            case 2: // JPG
            case 3: // PNG
                $Banner['banner_image'] = $Banner['banner_image_extern']; // Banner URL
                
                $arrBannersStat['banner_id'      ]     = $Banner['id'];
                $arrBannersStat['banner_style'   ]     = '';
                $arrBannersStat['banner_name'    ]     = specialchars(ampersand($Banner['banner_name']));
                $arrBannersStat['banner_alt'     ]     = specialchars(ampersand($Banner['banner_name']));
                $arrBannersStat['banner_title'   ]     = $Banner['banner_url'];
                $arrBannersStat['banner_url'     ]     = (strlen($Banner['banner_url']) <61 ? $Banner['banner_url'] : substr($Banner['banner_url'], 0, 28)."[...]".substr($Banner['banner_url'],-24,24) );
                $arrBannersStat['banner_image'   ]     = $Banner['banner_image'];
                $arrBannersStat['banner_width'   ]     = $intWidth;
                $arrBannersStat['banner_height'  ]     = $intHeight;
                $arrBannersStat['banner_prio'    ]     = $GLOBALS['TL_LANG']['tl_banner_stat']['prio'][$Banner['banner_weighting']];
                $arrBannersStat['banner_views'   ]     = ($MaxViewsClicks[0]) ? $Banner['banner_views']  .'<br>'.$GLOBALS['TL_LANG']['tl_banner_stat']['max_yes'] : $Banner['banner_views'];
                $arrBannersStat['banner_clicks'  ]     = ($MaxViewsClicks[1]) ? $Banner['banner_clicks'] .'<br>'.$GLOBALS['TL_LANG']['tl_banner_stat']['max_yes'] : $Banner['banner_clicks'];
                $arrBannersStat['banner_active'  ]     = $Banner['banner_active'];
                $arrBannersStat['banner_pub_class']   = $Banner['banner_published_class'];
                $arrBannersStat['banner_zero'    ]     = $GLOBALS['TL_LANG']['tl_banner_stat']['zero_text'];
                $arrBannersStat['banner_confirm' ]     = $GLOBALS['TL_LANG']['tl_banner_stat']['zero_confirm'];
                $arrBannersStat['banner_pic'     ]     = true; // Es ist ein Bild
                $arrBannersStat['banner_flash'   ]     = false;
                $arrBannersStat['banner_text'    ]     = false;
                break;
            default:
                $Banner['banner_image'] = $Banner['banner_image_extern']; // Banner URL
                
                $arrBannersStat['banner_pic'     ]     = true; 
                $arrBannersStat['banner_flash'   ]     = false;
                $arrBannersStat['banner_text'    ]     = false;
                $arrBannersStat['banner_prio'    ]     = $GLOBALS['TL_LANG']['tl_banner_stat']['prio'][$Banner['banner_weighting']];
                $arrBannersStat['banner_views'   ]     = ($MaxViewsClicks[0]) ? $Banner['banner_views']  .'<br>'.$GLOBALS['TL_LANG']['tl_banner_stat']['max_yes'] : $Banner['banner_views'];
                $arrBannersStat['banner_clicks'  ]     = ($MaxViewsClicks[1]) ? $Banner['banner_clicks'] .'<br>'.$GLOBALS['TL_LANG']['tl_banner_stat']['max_yes'] : $Banner['banner_clicks'];
                $arrBannersStat['banner_active'  ]     = $Banner['banner_active'];
                $arrBannersStat['banner_style'   ]     = 'color:red;font-weight:bold;';
                $arrBannersStat['banner_alt'     ]     = $GLOBALS['TL_LANG']['tl_banner_stat']['read_error'];
                $arrBannersStat['banner_url'     ]     = $Banner['banner_image'];
                break;
        } // switch
        return $arrBannersStat;
    } // addBannerExtern
    
    /**
     * Hook: addStatisticPanelLine
     * Search for registered BANNER HOOK: addStatisticPanelLine
     *
     * @return    string    HTML5 sourcecode | false
     * <code>
     * <!-- output minimum -->
     * <div class="tl_panel">
     *  <!-- <p>hello world</p> -->
     * </div>
     * </code>
     */
    protected function addStatisticPanelLineHook()
    {
        if (      isset($GLOBALS['TL_BANNER_HOOKS']['addStatisticPanelLine']) 
            && is_array($GLOBALS['TL_BANNER_HOOKS']['addStatisticPanelLine'])
           )
        {
            foreach ($GLOBALS['TL_BANNER_HOOKS']['addStatisticPanelLine'] as $callback)
            {
                $this->import($callback[0]);
                $result[] = $this->{$callback[0]}->{$callback[1]}($this->intCatID); //#170
            }
            return $result;
        }
        return false;
    }
    

    
} // class
