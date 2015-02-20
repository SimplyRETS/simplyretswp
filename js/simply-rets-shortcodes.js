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
               image : url + '/../img/defprop.jpg',
               onclick : function() {
                   ed.windowManager.open({
                       title: 'Embed SimplyRETS Listings',
                       body: [
                         {
                             type:  'listbox'
                           , name:  'type'
                           , label: 'Property Type'
                           , 'values': [
                                 {text: 'Residential', value: 'res'}
                               , {text: 'Condos'     , value: 'cnd'}
                               , {text: 'Rentals'    , value: 'rnt'}
                               , {text: 'All'        , value: ''}
                           ]
                         },
                         {
                             type:  'textbox'
                           , name:  'minprice'
                           , label: 'Minum Price'
                         },
                         {
                             type:  'textbox'
                           , name:  'maxprice'
                           , label: 'Maximum Price'
                         },
                         {
                             type:  'textbox'
                           , name:  'minbed'
                           , label: 'Minimum Bedrooms'
                         },
                         {
                             type:  'textbox'
                           , name:  'maxbed'
                           , label: 'Maximum Bedrooms'
                         },
                         {
                             type:  'textbox'
                           , name:  'minbath'
                           , label: 'Minimum bathrooms'
                         },
                         {
                             type:  'textbox'
                           , name:  'maxbath'
                           , label: 'Maximum Bathrooms'
                         }
                       ],
                       onsubmit: function(e) {
                           ed.focus();

                           var scStart  = '[sr_residential ';
                           var scEnd    = ']';
                           var type     = 'type="'     + e.data.type     + '" ';
                           var minprice = 'minprice="' + e.data.minprice + '" ';
                           var maxprice = 'maxprice="' + e.data.maxprice + '" ';
                           var minbed   = 'minbed="'   + e.data.minbed   + '" ';
                           var maxbed   = 'maxbed="'   + e.data.maxbed   + '" ';
                           var minbath  = 'minbath="'  + e.data.minbath  + '" ';
                           var maxbath  = 'maxbath="'  + e.data.maxbath  + '" ';
                         
                           ed.selection.setContent(
                                 scStart
                               + type
                               + minprice
                               + maxprice
                               + minbed
                               + maxbed
                               + minbath
                               + maxbath
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
