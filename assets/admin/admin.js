(function($){
  'use strict';

  function syncImageIds(){
    var ids=[]; $('#orkigal-image-grid .orkigal-image-card').each(function(){ids.push($(this).data('id'));});
    $('#orkigal-image-ids').val(ids.join(',')); $('#orkigal-image-empty').toggleClass('is-hidden',ids.length>0);
  }
  function syncVideoIds(){
    var ids=[]; $('#orkigal-video-grid .orkigal-video-card').each(function(){ids.push($(this).data('id'));});
    $('#orkigal-video-ids').val(ids.join(',')); $('#orkigal-video-empty').toggleClass('is-hidden',ids.length>0);
  }
  function escapeHtml(v){return $('<div>').text(v||'').html();}

  $(document).on('click','.orkigal-shortcode-copy',function(){
    var b=this,t=$(b).data('copy')||''; if(!t)return;
    function done(){$(b).addClass('is-copied');setTimeout(function(){$(b).removeClass('is-copied');},900);}
    if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(t).then(done);}else{var ta=document.createElement('textarea');ta.value=t;document.body.appendChild(ta);ta.select();document.execCommand('copy');ta.remove();done();}
  });

  $(function(){
    var grid=$('#orkigal-image-grid'); if(grid.length){grid.sortable({items:'.orkigal-image-card',placeholder:'orkigal-sort-placeholder',tolerance:'pointer',update:syncImageIds});}
    var vg=$('#orkigal-video-grid'); if(vg.length){vg.sortable({items:'.orkigal-video-card',placeholder:'orkigal-sort-placeholder',tolerance:'pointer',update:syncVideoIds});}
  });

  $(document).on('click','#orkigal-add-images',function(e){
    e.preventDefault();
    var frame=wp.media({title:orkigalAdmin.mediaTitle,button:{text:orkigalAdmin.mediaButton},multiple:'add',library:{type:'image'}});
    frame.on('open',function(){
      try{var state=frame.state();if(state&&state.get('selection')){state.get('selection').multiple=true;}}catch(err){}
    });
    frame.on('select',function(){
      var current={};$('#orkigal-image-grid .orkigal-image-card').each(function(){current[String($(this).data('id'))]=true;});
      frame.state().get('selection').each(function(model){
        var a=model.toJSON();if(current[String(a.id)])return;var thumb=(a.sizes&&a.sizes.thumbnail)?a.sizes.thumbnail.url:a.url;var title=a.title||('Image '+a.id);
        var html='<div class="orkigal-image-card" data-id="'+Number(a.id)+'" data-title="'+escapeHtml(String(title).toLowerCase())+'"><span class="orkigal-drag"><span class="dashicons dashicons-move"></span></span><img src="'+escapeHtml(thumb)+'" alt=""><div><span title="'+escapeHtml(title)+'">'+escapeHtml(title)+'</span><button type="button" class="orkigal-remove-image" aria-label="Remove image">×</button></div></div>';
        $('#orkigal-image-grid').append(html);current[String(a.id)]=true;
      });syncImageIds();
    });frame.open();
  });

  $(document).on('click','#orkigal-add-video',function(e){
    e.preventDefault();var frame=wp.media({title:orkigalAdmin.videoTitle,button:{text:orkigalAdmin.videoButton},multiple:'add',library:{type:'video'}});
    frame.on('select',function(){var current={};$('#orkigal-video-grid .orkigal-video-card').each(function(){current[String($(this).data('id'))]=true;});frame.state().get('selection').each(function(model){var a=model.toJSON();if(current[String(a.id)])return;var title=a.title||a.filename||('Video '+a.id);var html='<div class="orkigal-video-card" data-id="'+Number(a.id)+'"><div class="orkigal-video-thumb"><span class="dashicons dashicons-video-alt3"></span></div><div class="orkigal-video-meta"><span title="'+escapeHtml(title)+'">'+escapeHtml(title)+'</span><button type="button" class="orkigal-remove-video" aria-label="Remove video">×</button></div></div>';$('#orkigal-video-grid').append(html);current[String(a.id)]=true;});syncVideoIds();});frame.open();
  });

  $(document).on('click','.orkigal-remove-image',function(){$(this).closest('.orkigal-image-card').remove();syncImageIds();});
  $(document).on('click','.orkigal-remove-video',function(){$(this).closest('.orkigal-video-card').remove();syncVideoIds();});

  $(document).on('change','#orkigal-sort-images',function(){
    var mode=this.value,$grid=$('#orkigal-image-grid'),items=$grid.children('.orkigal-image-card').get();
    if(mode==='reverse'){items.reverse();}
    if(mode==='title'){items.sort(function(a,b){return String($(a).data('title')||'').localeCompare(String($(b).data('title')||''));});}
    if(mode==='id'){items.sort(function(a,b){return Number($(a).data('id'))-Number($(b).data('id'));});}
    $.each(items,function(_,el){$grid.append(el);});syncImageIds();
  });

  $(document).on('change','#orkigal-select-all',function(){$('#orkigal-image-grid .orkigal-image-card').toggleClass('orkigal-checkbox-selected',this.checked);});

  $(document).on('input','#orkigal-gallery-search',function(){
    var q=String(this.value||'').toLowerCase().trim();$('#orkigal-gallery-table .orkigal-gallery-table-row:not(.is-head)').each(function(){var name=String($(this).data('gallery-name')||'');$(this).toggle(!q||name.indexOf(q)!==-1);});
  });

  function updateLayoutCards(){
    $('.orkigal-layout-card').each(function(){$(this).toggleClass('is-selected',$(this).find('input[type="radio"]').is(':checked'));});
    var layout=$('input[name="settings[layout]"]:checked').val()||'grid';
    $('.orkigal-motion-setting').toggle(layout==='carousel'||layout==='slideshow');
    $('.orkigal-stage-setting').toggle(layout==='slideshow');
  }
  $(document).on('change','.orkigal-layout-card input[type="radio"]',updateLayoutCards);
  $(updateLayoutCards);

  function updateAdminPreview(){
    var root=$('.orkigal-preview-canvas .orkigal-root');if(!root.length)return;
    var layout=$('input[name="settings[layout]"]:checked').val();if(layout){root.attr('class',function(i,c){return String(c).replace(/orkigal-layout-\S+/g,'').trim();}).addClass('orkigal-layout-'+layout);}
    var ratio=$('[data-preview-setting="ratio"]').val();if(ratio){root.attr('class',function(i,c){return String(c).replace(/orkigal-ratio-\S+/g,'').trim();}).addClass('orkigal-ratio-'+ratio);}
    var fit=$('[data-preview-setting="fit"]').val();if(fit){root.attr('class',function(i,c){return String(c).replace(/orkigal-fit-\S+/g,'').trim();}).addClass('orkigal-fit-'+fit);}
    var d=$('[data-preview-setting="columns_desktop"]').val(),t=$('[data-preview-setting="columns_tablet"]').val(),m=$('[data-preview-setting="columns_mobile"]').val(),g=$('[data-preview-setting="gap"]').val(),r=$('[data-preview-setting="radius"]').val(),h=$('[data-preview-setting="stage_height"]').val();
    if(d)root[0].style.setProperty('--orkigal-cols',d);if(t)root[0].style.setProperty('--orkigal-cols-tablet',t);if(m)root[0].style.setProperty('--orkigal-cols-mobile',m);if(g!==undefined)root[0].style.setProperty('--orkigal-gap',g+'px');if(r!==undefined)root[0].style.setProperty('--orkigal-radius',r+'px');if(h)root[0].style.setProperty('--orkigal-stage',h+'px');
  }
  $(document).on('change input','.orkigal-layout-card input,[data-preview-setting]',updateAdminPreview);
})(jQuery);
