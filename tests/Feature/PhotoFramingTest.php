<?php

/*
| Photos in fixed frames (cards, thumbnails): a photo that does not fit is trimmed
| 30% from the top and 70% from the bottom, so faces (above the middle) stay in view.
| 6 Nov 2026: the browser's centred crop cut off foreheads in the square thumbnails
| and on tall phone photos.
*/

it('keeps faces in view in every page layout', function () {
    foreach (glob(resource_path('views/components/layouts/*.blade.php')) as $layout) {
        expect(file_get_contents($layout))->toContain('<style>img.object-cover{object-position:50% 30%}</style>');
    }
});
