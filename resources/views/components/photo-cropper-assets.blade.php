{{-- Cropper.js library + brand-color overrides for the crop UI.

     Used by both /register/photo (lightweight one-shot signup upload) and
     /manage-photos (full photo management page). The Alpine components that
     drive the cropper (registerPhotoEditor + photoManagerEditor) stay in
     their respective views because they're tuned to different UX contexts
     — inline editor vs modal editor, single-shot vs multi-photo. Only this
     library + style boilerplate is shared.

     If/when we change the Cropper.js version, switch from CDN to a bundled
     package, or restyle the crop UI, this is the single place to edit. --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<style>
    .cropper-bg { background-image: none !important; background-color: #f3f4f6 !important; }
    .cropper-modal { background-color: rgba(17, 24, 39, 0.5) !important; }
    .cropper-view-box { outline: 2px solid rgba(255, 255, 255, 0.9); outline-color: rgba(255, 255, 255, 0.9); }
    .cropper-line, .cropper-point { background-color: var(--color-primary, #8B1D91) !important; }
    .cropper-dashed { border-color: rgba(255, 255, 255, 0.6); }
</style>
