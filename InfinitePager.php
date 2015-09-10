<?php
/**
 * Created by PhpStorm.
 * User: Victor
 * Date: 03.07.2015
 * Time: 20:37
 */
namespace terrabo\infinitesrc;

use yii\base\Widget;
use yii\data\Pagination;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\bootstrap\Button;
use yii\helpers\Html;
use yii\web\View;

class InfinitePager extends Widget {
    public $widgetId;
    /**
     * @var Pagination the pagination object that this pager is associated with.
     * You must set this property in order to make InfiniteScrollPager work.
     */
    public $pagination;
    /**
     * @var array infinite-scroll jQuery plugin options
     */
    public $pluginOptions = [];
    /**
     * @var string CSS class of a tag that encapsulates items
     */
    public $itemsCssClass;
    /**
     * @var string the CSS class for the "next" page button.
     */
    public $nextPageCssClass = 'next';
    public $nextPageLabel = 'Загрузить еще';
    public $hideOnSinglePage = true;
    public $nextButtonCssClass='';
    public function init() {
        if ($this->pagination === null) {
            throw new InvalidConfigException('The "pagination" property must be set.');
        }
        // тут можно будет привязать js для скрипта бесконечного скрола
        //InfiniteScrollAsset::register($this->view);
        $widgetSelector = '#' . $this->widgetId;
// Set default plugin selectors / options if not configured
        if (is_null(ArrayHelper::getValue($this->pluginOptions, 'maxPage', null)))
            $this->pluginOptions['maxPage'] = $this->pagination->getPageCount();
        if (is_null(ArrayHelper::getValue($this->pluginOptions, 'contentSelector', null)))
            $this->pluginOptions['contentSelector'] = $widgetSelector . ' .' . $this->itemsCssClass;
        if (is_null(ArrayHelper::getValue($this->pluginOptions, 'nextSelector', null)))
            $this->pluginOptions['nextSelector'] = $widgetSelector . ' .' . $this->nextPageCssClass . ":first a:first";
        if (is_null(ArrayHelper::getValue($this->pluginOptions, 'nextButtonCssClass', null)))
            $this->pluginOptions['nextButtonCssClass'] = $this->nextButtonCssClass;
        //var_dump($this->pluginOptions);exit();
    }
    public function run() {
        parent::run();
        echo $this->renderPageButtons();
        $this->view->registerJs($this->attachScript(),View::POS_END, $this->widgetId . '-infinite-scroll');
    }
    /**
     * Renders the page buttons.
     * @return string the rendering result
     */
    protected function renderPageButtons()
    {
        $pageCount = $this->pagination->getPageCount();
        if ($pageCount < 2) {
            return '';
        }
        $currentPage = $this->pagination->getPage(true)+1;
        if($currentPage<$pageCount) {
            return "<div style=\"display:none;\" class=\"next\">".Html::a('next', $this->pagination->createUrl($currentPage))."</div><div class=\"loading\" style=\"padding: 1em 0;text-align: center;\">".Button::widget([
                'label' => $this->nextPageLabel,
                'options' => [
                    'class' => ($this->nextButtonCssClass)?$this->nextButtonCssClass:'btn',
                    'data-loading-text'=> 'Загружаем...',
                    'onclick'=>'return load_'.$this->widgetId.'($(this));',
                    'id'=>'load-more-btn_'.$this->widgetId,
                ],
            ])."</div>";
        } else {
            return '';
        }


    }
    protected function attachScript() {
        $js = <<<JS
$(document).on('scroll',function(e){
	var next=$('#load-more-btn_{$this->widgetId}');
	if(next.is(':visible')) {
		var top=next.offset().top-$(window).height()-$(window).scrollTop()-100;
		if(top<=0&&!next.is(':disabled')) {
			next.click();
		}
	}
});
function load_{$this->widgetId}(elem) {
    var widget=$('#{$this->widgetId}');
    var next=$('{$this->pluginOptions['nextSelector']}');
    var url=next.attr('href');
    if(!elem.is(':disabled')) {
        elem.button('loading');
        if (url) {
            $.ajax({
                type: "POST",
                url: url,
                dataType: 'HTML',
                success: function (data) {
                    var items=$(data).find('.{$this->itemsCssClass}');
                    if (items.length) {
                        widget.find('.{$this->itemsCssClass}:last').after(items);
                    }
                    var loaded_next=$(data).find('.{$this->nextPageCssClass} a:first');
                    if (next.length&&loaded_next.length) {
                        next.attr('href', loaded_next.attr('href'));
                    } else {
                        elem.hide();
                        next.attr('href', '');
                    }
                    elem.button('reset');
                },
                fail: function () {
                    elem.button('reset');
                }
            });
        } else {
            elem.hide();
        }
    }
    return false;
}
JS;
        return $js;
    }
}