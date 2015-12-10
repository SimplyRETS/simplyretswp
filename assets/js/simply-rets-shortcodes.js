/**
 *
 * simply-rets-shortcodes.js - Copyright (c) 2014-2015 SimplyRETS
 * 
 * Author: Cody Reichert
 * License: GPLv3 (http://www.gnu.org/licenses/gpl.html)
 *
 */

jQuery(document).ready(function() {

   tinymce.create('tinymce.plugins.simplyRets', {
       init: function(ed, url) {
           ed.addButton('simplyRets', {
               title: 'Insert SimplyRETS Listings',
               image: url + '/../img/icon-128x128.png',
               onclick: function() {
                   ed.windowManager.open({
                       title: 'SimplyRETS Page Builder',
                       body: [
                         {
                             type: 'container'
                           , html: '<span style="max-width:400px">Use the filters below to determine the type '
                                  +'and amount of</span><br><span>  listings that you want to show on this page.'
                                  +'</span><br>'
                         },
                         {
                             type: 'container'
                           , html: '<span style="color:#a00;font-weight:bold;max-width:400px"><strong>'
                                 + '  Note: These are only a few of many more available filters.<br>'
                                 + '  To see how to use more short-codes and filters, visit the <br> '
                                 + '  <a href="https://wordpress.org/plugins/simply-rets/other_notes/#Shortcodes"'
                                 + '     target="_blank" style="text-decoration:underline"'
                                 + '> Short-Code Documentation</a>.'
                                 + '</strong></span>'
                         },
                         {
                             type:  'listbox'
                           , label: 'Type of Property'
                           , name:  'type'
                           , 'values': [
                                 {text: 'All'        , value: ''}
                               , {text: 'Residential', value: 'res'}
                               , {text: 'Condos'     , value: 'cnd'}
                               , {text: 'Rentals'    , value: 'rnt'}
                           ]
                         },
                         {
                             type: 'container'
                           , html: '<br>'
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
                             type: 'container'
                           , html: '<br>'
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
                             type: 'container'
                           , html: '<br>'
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
                         },
                         {
                             type: 'container'
                           , html: '<br>'
                         },
                         {
                             type:  'textbox'
                           , name:  'agent'
                           , label: 'Agent MLS ID'
                         },
                         {
                             type:  'textbox'
                           , name:  'limit'
                           , label: 'Amount of listings to show'
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
                           var agent    = e.data.agent !== "" && e.data.agent !== undefined
                                            ? 'agent="' + e.data.agent + '" '
                                            : '';
                           var limit    = e.data.limit !== "" && e.data.limit !== undefined
                                            ? 'limit="' + e.data.limit + '" '
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
                               + agent
                               + limit
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
