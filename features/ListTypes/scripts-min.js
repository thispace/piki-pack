!function($){var e={configure:function(e){return this.each(function(){var t={afterLoad:!1,afterChange:!1,afterClear:!1,afterReset:!1},r=$.extend({},t,e);r.$=$(this),r.self=this,r.programas=$(".list-items",r.$),r.programas.masonry({columnWidth:"span.grid-sizer",itemSelector:"a.item-prog",percentPosition:!0}),r.items=r.programas.children("a.item-prog"),r._items=r.items.clone(),r.getItems=function(){return r.programas.children("a.item-prog").not("deleted")},r.cookieName=r.self.id+"_list_switcher_type",r.cookie=window.getCookie(r.cookieName),r.actualMode=void 0===r.cookie?"grid":r.cookie,r.beforeMode=!1,r.header=$("#header-programacao").first(),r.calendar=r.$.children(".calendario"),r.$.data("listTypes",r),r.switcher=$('.list-type-switcher[rel="'+r.self.id+'"]'),r.switcher.addClass(r.actualMode),r.switcher.children("a").on("click",function(e){e.preventDefault();var t=$(this).attr("rel");r.actualMode!==t&&(r.beforeMode=r.actualMode,"list"===t?r.$.listTypes("setMode","list"):r.$.listTypes("setMode","grid"))}),"grid"===r.actualMode?r.$.listTypes("setMode","grid"):r.$.listTypes("setMode","list"),r.timeout=!1,r.resize=function(){var e=window.innerWidth||document.documentElement.clientWidth||document.body.clientWidth;640>=e?"phone"!==r.actualMode&&r.$.listTypes("setMode","phone"):"phone"===r.actualMode&&("grid"===r.beforeMode?r.$.listTypes("setMode","grid"):r.$.listTypes("setMode","list"))},$(window).bind("resize",function(){r.$.listTypes("resizeBind")}).resize()})},setMode:function(e,t){return this.each(function(){e.actualMode=t,e.$.removeClass("list-mode grid-mode phone-mode").addClass(t+"-mode"),e.switcher.removeClass("list grid phone").addClass(t),window.setCookie(e.cookieName,t,365),e.$.listTypes("resetItems",!0),e.afterChange!==!1&&e.afterChange.call(e.self,e)})},removeItems:function(e,t){return this.each(function(){var r=e.getItems().filter(t).addClass("deleted");e.programas.masonry("remove",r).masonry("layout")})},restoreItems:function(e,t){return this.each(function(){var r=e._items.filter(t).clone();e.programas.prepend(r).masonry("prepended",r).masonry("layout")})},clear:function(e,t){return this.each(function(){var r=e.getItems().addClass("deleted");e.programas.masonry("remove",r),e.afterClear!==!1&&e.afterClear.call(e.self,e),void 0!==t&&t.call(e.self,e)})},resetItems:function(e,t){return this.each(function(){e.$.listTypes("clear"),t===!0&&e._items.removeClass("disabled");var r=e._items.not(".disabled").clone();e.programas.prepend(r).masonry("prepended",r).masonry("layout"),e.afterReset!==!1&&e.afterReset.call(e.self,e)})},resizeBind:function(e){return this.each(function(){clearTimeout(e.timeout),e.timeout=setTimeout(e.resize,200)})}};$.fn.listTypes=function(t){var r=this.data("listTypes");if(void 0===r)return e.configure.apply(this,arguments);if(e[t]){var i=Array.prototype.slice.call(arguments,1);return i.unshift(r),e[t].apply(this,i)}}}(jQuery);