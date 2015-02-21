/**
 *
 * simply-rets-shortcodes.js - Copyright (c) Reichert Brothers 2014
 * 
 * Author: Cody Reichert, Reichert Brothers
 * License: GPLv3 (http://www.gnu.org/licenses/gpl.html)
 *
**/

jQuery(document).ready(function() {

   tinymce.create('tinymce.plugins.simplyRets', {
       init : function(ed, url) {
           ed.addButton('simplyRets', {
               title : 'Insert SimplyRETS Listings',
               image : url + '/../img/icon-128x128.png',
               onclick : function() {
                   ed.windowManager.open({
                       title: 'Embed SimplyRETS Listings',
                       body: [
                         {
                             type:  'listbox'
                           , name:  'type'
                           , label: 'Property Type'
                           , 'values': [
                                 {text: 'All'        , value: ''}
                               , {text: 'Residential', value: 'res'}
                               , {text: 'Condos'     , value: 'cnd'}
                               , {text: 'Rentals'    , value: 'rnt'}
                           ]
                         },
                         {
                             type:  'textbox'
                           , name:  'minprice'
                           , label: 'Minimum Price'
                         },
                         {
                             type:  'textbox'
                           , name:  'maxprice'
                           , label: 'Maximum Price'
                         },
                         {
                             type:  'textbox'
                           , name:  'minbeds'
                           , label: 'Minimum Bedrooms'
                         },
                         {
                             type:  'textbox'
                           , name:  'maxbeds'
                           , label: 'Maximum Bedrooms'
                         },
                         {
                             type:  'textbox'
                           , name:  'minbaths'
                           , label: 'Minimum bathrooms'
                         },
                         {
                             type:  'textbox'
                           , name:  'maxbaths'
                           , label: 'Maximum Bathrooms'
                         }
                       ],
                       onsubmit: function(e) {
                           ed.focus();

                           var scStart  = '[sr_residential ';
                           var scEnd    = ']';
                           var type     = e.data.type !== "" && e.data.type !== undefined
                                            ? 'type="' + e.data.type + '" '
                                            : '';
                           var minprice = e.data.minprice !== "" && e.data.minprice !== undefined
                                            ? 'minprice="' + e.data.minprice + '" '
                                            : '';
                           var maxprice = e.data.maxprice !== "" && e.data.maxprice !== undefined
                                            ? 'maxprice="' + e.data.maxprice + '" '
                                            : '';
                           var minbeds   = e.data.minbeds !== "" && e.data.minbeds !== undefined
                                            ? 'minbeds="' + e.data.minbeds + '" '
                                            : '';
                           var maxbeds   = e.data.maxbeds !== "" && e.data.maxbeds !== undefined
                                            ? 'maxbeds="' + e.data.maxbeds + '" '
                                            : '';
                           var minbaths = e.data.minbaths !== "" && e.data.minbaths !== undefined
                                            ? 'minbaths="' + e.data.minbaths + '" '
                                            : '';
                           var maxbaths = e.data.maxbaths !== "" && e.data.maxbaths !== undefined
                                            ? 'maxbaths="' + e.data.maxbaths + '" '
                                            : '';
                         
                           ed.selection.setContent(
                                 scStart
                               + type
                               + minprice
                               + maxprice
                               + minbeds
                               + maxbeds
                               + minbaths
                               + maxbaths
                               + scEnd
                               + ed.selection.getContent()
                           );

                       }
                   });
               }
            });
       },
       createControl : function(n, cm) {
           return null;
       }
   });

   tinymce.PluginManager.add('simplyRets', tinymce.plugins.simplyRets);
});
