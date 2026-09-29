(function(blocks,element,components,blockEditor,i18n){
  'use strict';
  var el=element.createElement;
  var Fragment=element.Fragment;
  var InspectorControls=blockEditor.InspectorControls;
  var SelectControl=components.SelectControl;
  var Placeholder=components.Placeholder;
  var Button=components.Button;
  var __=i18n.__;
  var data=(window.orkigalBlockData&&window.orkigalBlockData.galleries)||[];
  var options=[{label:__('Select a gallery','orki-gallery'),value:0}].concat(data.map(function(g){return{label:g.title+' (#'+g.id+')',value:g.id};}));
  function galleryById(id){for(var i=0;i<data.length;i++){if(Number(data[i].id)===Number(id))return data[i];}return null;}
  blocks.registerBlockType('orki/gallery',{
    title:__('Orki Gallery','orki-gallery'),
    description:__('Display a saved Orki Gallery.','orki-gallery'),
    icon:'format-gallery',
    category:'media',
    attributes:{galleryId:{type:'integer',default:0}},
    edit:function(props){
      var selected=galleryById(props.attributes.galleryId);
      return el(Fragment,null,
        el(InspectorControls,null,el('div',{style:{padding:'16px'}},el(SelectControl,{label:__('Gallery','orki-gallery'),value:props.attributes.galleryId||0,options:options,onChange:function(v){props.setAttributes({galleryId:parseInt(v,10)||0});}}))),
        selected?el('div',{className:'orkigal-block-preview'},
          selected.thumb?el('img',{src:selected.thumb,alt:'',style:{width:'84px',height:'84px',objectFit:'cover',borderRadius:'8px'}}):null,
          el('div',null,el('strong',null,selected.title),el('p',null,selected.count+' '+__('media items','orki-gallery')))
        ):el(Placeholder,{icon:'format-gallery',label:__('Orki Gallery','orki-gallery'),instructions:__('Choose a saved gallery in the block settings.','orki-gallery')},
          el(SelectControl,{value:0,options:options,onChange:function(v){props.setAttributes({galleryId:parseInt(v,10)||0});}}),
          window.orkigalBlockData&&window.orkigalBlockData.createUrl?el(Button,{variant:'secondary',href:window.orkigalBlockData.createUrl,target:'_blank'},__('Create a gallery','orki-gallery')):null
        )
      );
    },
    save:function(){return null;}
  });
})(window.wp.blocks,window.wp.element,window.wp.components,window.wp.blockEditor,window.wp.i18n);
