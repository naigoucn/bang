Bang 主题 · 本地第三方资源
============================

PhotoSwipe 5.4.4
  来源：npm 包 photoswipe@5.4.4（https://www.npmjs.com/package/photoswipe）
  项目：https://photoswipe.com/  ·  https://github.com/dimsemenov/PhotoSwipe
  许可：MIT，见同目录 LICENSE.txt

包含文件
  photoswipe.umd.min.js           核心（UMD，挂到 window.PhotoSwipe）
  photoswipe-lightbox.umd.min.js  事件委托封装（UMD，挂到 window.PhotoSwipeLightbox）
  photoswipe.css                  官方基础样式（保持原样，勿直接修改）

重要约定
  1. 这些文件是从 npm tarball 的 dist/ 目录原样复制到主题内的，
     前台一律通过 get_template_directory_uri() 引用，**不使用任何 CDN / 外链**。
  2. 主题自身的视觉覆盖统一写在 assets/css/photoswipe-bang.css，
     由它依赖官方 CSS 后再加载，避免升级库时丢失改动。
  3. 升级方式：npm pack photoswipe@<版本> → 解出 dist → 覆盖本目录，
     同时更新 functions.php 里 pk_assets() 注册的版本号。
  4. app.js 里用 new window.PhotoSwipe({ dataSource, index, ... }) 直接构造，
     尺寸取自已加载的缩略图 naturalWidth/naturalHeight，
     data.element 指向 <a> 以启用「从缩略图放大浮起」的转场。
  5. 若该脚本加载失败，灯箱退回原生 <dialog>（或纯链接打开原图），
     不会让图片点不开。
