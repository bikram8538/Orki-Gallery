(function(){
  'use strict';

  var lightbox=null;
  var lightboxItems=[];
  var lightboxIndex=0;
  var lightboxTrigger=null;
  var reducedMotion=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function ensureLightbox(){
    if(lightbox)return lightbox;
    lightbox=document.createElement('div');
    lightbox.className='orkigal-lightbox';
    lightbox.setAttribute('role','dialog');
    lightbox.setAttribute('aria-modal','true');
    lightbox.setAttribute('aria-label','Image viewer');
    lightbox.innerHTML='<button type="button" class="orkigal-lightbox__close" aria-label="Close">×</button><button type="button" class="orkigal-lightbox__prev" aria-label="Previous image">‹</button><div class="orkigal-lightbox__inner"><img class="orkigal-lightbox__image" alt=""><div class="orkigal-lightbox__caption"></div></div><button type="button" class="orkigal-lightbox__next" aria-label="Next image">›</button>';
    document.body.appendChild(lightbox);
    lightbox.addEventListener('click',function(e){if(e.target===lightbox||e.target.closest('.orkigal-lightbox__close'))closeLightbox();});
    lightbox.querySelector('.orkigal-lightbox__prev').addEventListener('click',function(){moveLightbox(-1);});
    lightbox.querySelector('.orkigal-lightbox__next').addEventListener('click',function(){moveLightbox(1);});
    return lightbox;
  }

  function updateLightbox(){
    if(!lightboxItems.length)return;
    lightboxIndex=Math.max(0,Math.min(lightboxIndex,lightboxItems.length-1));
    var item=lightboxItems[lightboxIndex];
    var lb=ensureLightbox();
    var img=lb.querySelector('.orkigal-lightbox__image');
    var cap=lb.querySelector('.orkigal-lightbox__caption');
    img.src=item.dataset.full||'';
    img.alt=item.dataset.caption||'';
    cap.textContent=item.dataset.caption||'';
    lb.querySelector('.orkigal-lightbox__prev').hidden=lightboxItems.length<2;
    lb.querySelector('.orkigal-lightbox__next').hidden=lightboxItems.length<2;
  }

  function openLightbox(trigger){
    var root=trigger.closest('.orkigal-root');
    lightboxItems=[].slice.call(root.querySelectorAll('.orkigal-media-button[data-full]'));
    lightboxIndex=Math.max(0,lightboxItems.indexOf(trigger));
    lightboxTrigger=trigger;
    var lb=ensureLightbox();
    var styles=getComputedStyle(root);
    ['--orkigal-lightbox-bg','--orkigal-lightbox-control-bg','--orkigal-lightbox-control-color'].forEach(function(name){var value=styles.getPropertyValue(name);if(value)lb.style.setProperty(name,value.trim());});
    updateLightbox();
    lb.classList.add('is-open');
    document.documentElement.style.overflow='hidden';
    lb.querySelector('.orkigal-lightbox__close').focus();
  }

  function moveLightbox(delta){
    if(lightboxItems.length<2)return;
    lightboxIndex=(lightboxIndex+delta+lightboxItems.length)%lightboxItems.length;
    updateLightbox();
  }

  function closeLightbox(){
    if(!lightbox)return;
    lightbox.classList.remove('is-open');
    document.documentElement.style.overflow='';
    lightbox.querySelector('.orkigal-lightbox__image').removeAttribute('src');
    if(lightboxTrigger&&document.contains(lightboxTrigger))lightboxTrigger.focus();
    lightboxTrigger=null;
  }

  function getVisible(root){
    var styles=getComputedStyle(root),w=root.getBoundingClientRect().width||window.innerWidth,key='--orkigal-cols';
    if(w<=600)key='--orkigal-cols-mobile';else if(w<=900)key='--orkigal-cols-tablet';
    return Math.max(1,parseInt(styles.getPropertyValue(key),10)||1);
  }

  function swipe(el,onSwipe){
    var startX=null,startY=null;
    el.addEventListener('pointerdown',function(e){if(e.pointerType==='mouse'&&e.button!==0)return;startX=e.clientX;startY=e.clientY;});
    el.addEventListener('pointerup',function(e){if(startX===null)return;var dx=e.clientX-startX,dy=e.clientY-startY;startX=null;startY=null;if(Math.abs(dx)>45&&Math.abs(dx)>Math.abs(dy)*1.2)onSwipe(dx<0?1:-1);});
  }

  function initCarousel(root){
    if(root.dataset.orkiInit==='1')return;
    root.dataset.orkiInit='1';
    var track=root.querySelector('.orkigal-grid'),items=[].slice.call(track?track.children:[]);if(!track||!items.length)return;
    var index=0,prev=root.querySelector('.orkigal-prev'),next=root.querySelector('.orkigal-next'),dots=root.querySelector('.orkigal-dots'),timer=null,hovered=false;
    var loop=root.dataset.loop==='1',pauseHover=root.dataset.pauseHover==='1',autoplay=root.dataset.autoplay==='1'&&!reducedMotion,speed=Math.max(1000,parseInt(root.dataset.autoplaySpeed,10)||3500);
    function maxIndex(){return Math.max(0,items.length-getVisible(root));}
    function setIndex(nextIndex){var max=maxIndex();if(loop&&max>0){if(nextIndex<0)nextIndex=max;if(nextIndex>max)nextIndex=0;}index=Math.max(0,Math.min(nextIndex,max));update();}
    function buildDots(){if(!dots)return;dots.innerHTML='';for(var i=0;i<=maxIndex();i++){(function(n){var d=document.createElement('button');d.type='button';d.className='orkigal-dot'+(n===index?' is-active':'');d.setAttribute('aria-label','Go to slide '+(n+1));d.setAttribute('aria-current',n===index?'true':'false');d.addEventListener('click',function(){setIndex(n);restart();});dots.appendChild(d);})(i);}}
    function update(){index=Math.max(0,Math.min(index,maxIndex()));var item=items[0];if(!item)return;var gap=parseFloat(getComputedStyle(track).gap)||0,step=item.getBoundingClientRect().width+gap;track.style.transform='translate3d('+(-index*step)+'px,0,0)';if(prev)prev.disabled=!loop&&index<=0;if(next)next.disabled=!loop&&index>=maxIndex();buildDots();}
    function tick(){if(hovered)return;if(!loop&&index>=maxIndex()){stop();return;}setIndex(index+1);}
    function stop(){if(timer){clearInterval(timer);timer=null;}}
    function start(){stop();if(autoplay&&maxIndex()>0)timer=setInterval(tick,speed);}
    function restart(){start();}
    if(prev)prev.addEventListener('click',function(){setIndex(index-1);restart();});
    if(next)next.addEventListener('click',function(){setIndex(index+1);restart();});
    root.addEventListener('keydown',function(e){if(e.key==='ArrowLeft'){e.preventDefault();setIndex(index-1);restart();}if(e.key==='ArrowRight'){e.preventDefault();setIndex(index+1);restart();}});
    if(pauseHover){root.addEventListener('mouseenter',function(){hovered=true;});root.addEventListener('mouseleave',function(){hovered=false;});root.addEventListener('focusin',function(){hovered=true;});root.addEventListener('focusout',function(){hovered=false;});}
    swipe(track,function(dir){setIndex(index+dir);restart();});
    window.addEventListener('resize',update,{passive:true});
    update();start();
  }

  function initSlideshow(root){
    if(root.dataset.orkiInit==='1')return;
    root.dataset.orkiInit='1';
    var items=[].slice.call(root.querySelectorAll('.orkigal-grid>.orkigal-item'));if(!items.length)return;
    var index=0,prev=root.querySelector('.orkigal-prev'),next=root.querySelector('.orkigal-next'),dots=root.querySelector('.orkigal-dots'),timer=null,hovered=false;
    var loop=root.dataset.loop==='1',pauseHover=root.dataset.pauseHover==='1',autoplay=root.dataset.autoplay==='1'&&!reducedMotion,speed=Math.max(1000,parseInt(root.dataset.autoplaySpeed,10)||3500);
    function pauseVideos(){items.forEach(function(el,i){if(i!==index){var v=el.querySelector('video');if(v&&!v.paused)v.pause();}});}
    function setIndex(nextIndex){if(loop){if(nextIndex<0)nextIndex=items.length-1;if(nextIndex>=items.length)nextIndex=0;}index=Math.max(0,Math.min(nextIndex,items.length-1));update();}
    function buildDots(){if(!dots)return;dots.innerHTML='';items.forEach(function(el,n){var d=document.createElement('button');d.type='button';d.className='orkigal-dot'+(n===index?' is-active':'');d.setAttribute('aria-label','Show slide '+(n+1));d.setAttribute('aria-current',n===index?'true':'false');d.addEventListener('click',function(){setIndex(n);restart();});dots.appendChild(d);});}
    function update(){items.forEach(function(el,i){el.classList.toggle('is-active',i===index);el.setAttribute('aria-hidden',i===index?'false':'true');});if(prev)prev.disabled=!loop&&index<=0;if(next)next.disabled=!loop&&index>=items.length-1;buildDots();pauseVideos();}
    function tick(){if(hovered)return;if(!loop&&index>=items.length-1){stop();return;}setIndex(index+1);}
    function stop(){if(timer){clearInterval(timer);timer=null;}}
    function start(){stop();if(autoplay&&items.length>1)timer=setInterval(tick,speed);}
    function restart(){start();}
    if(prev)prev.addEventListener('click',function(){setIndex(index-1);restart();});if(next)next.addEventListener('click',function(){setIndex(index+1);restart();});
    root.addEventListener('keydown',function(e){if(e.key==='ArrowLeft'){e.preventDefault();setIndex(index-1);restart();}if(e.key==='ArrowRight'){e.preventDefault();setIndex(index+1);restart();}});
    if(pauseHover){root.addEventListener('mouseenter',function(){hovered=true;});root.addEventListener('mouseleave',function(){hovered=false;});root.addEventListener('focusin',function(){hovered=true;});root.addEventListener('focusout',function(){hovered=false;});}
    swipe(root.querySelector('.orkigal-grid'),function(dir){setIndex(index+dir);restart();});
    update();start();
  }

  function scan(scope){
    scope=scope||document;
    if(scope.matches&&scope.matches('.orkigal-layout-carousel'))initCarousel(scope);
    if(scope.matches&&scope.matches('.orkigal-layout-slideshow'))initSlideshow(scope);
    if(scope.querySelectorAll){scope.querySelectorAll('.orkigal-layout-carousel').forEach(initCarousel);scope.querySelectorAll('.orkigal-layout-slideshow').forEach(initSlideshow);}
  }

  document.addEventListener('click',function(e){var button=e.target.closest('.orkigal-root[data-lightbox="1"] .orkigal-media-button[data-full]');if(!button)return;e.preventDefault();openLightbox(button);});
  document.addEventListener('keydown',function(e){
    if(!lightbox||!lightbox.classList.contains('is-open'))return;
    if(e.key==='Escape'){e.preventDefault();closeLightbox();}
    if(e.key==='ArrowLeft'){e.preventDefault();moveLightbox(-1);}
    if(e.key==='ArrowRight'){e.preventDefault();moveLightbox(1);}
    if(e.key==='Tab'){
      var focusable=[].slice.call(lightbox.querySelectorAll('button:not([hidden])'));if(!focusable.length)return;var first=focusable[0],last=focusable[focusable.length-1];if(e.shiftKey&&document.activeElement===first){e.preventDefault();last.focus();}else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first.focus();}
    }
  });
  document.addEventListener('DOMContentLoaded',function(){scan(document);});
  if(window.MutationObserver){new MutationObserver(function(records){records.forEach(function(r){r.addedNodes.forEach(function(n){if(n.nodeType===1)scan(n);});});}).observe(document.documentElement,{childList:true,subtree:true});}
  window.OrkiGalleryFrontend={refresh:function(scope){scan(scope||document);}};
})();
