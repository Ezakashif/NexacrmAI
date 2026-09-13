/**
 * Photo upload with fixed-frame adjust UX.
 * User chooses/drops a photo, then drags it inside a locked circular frame.
 */
(function (window, document) {
    'use strict';

    var OUTPUT_SIZE = 512;
    var ACCEPTED = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    function $(root, sel) {
        return root.querySelector(sel);
    }

    function show(el) {
        if (el) {
            el.hidden = false;
        }
    }

    function hide(el) {
        if (el) {
            el.hidden = true;
        }
    }

    function setError(root, message) {
        var err = $(root, '[data-icu-error]');
        if (!err) {
            return;
        }
        if (message) {
            err.textContent = message;
            show(err);
            root.classList.add('is-invalid');
        } else {
            err.textContent = '';
            hide(err);
            root.classList.remove('is-invalid');
        }
    }

    function formatBytes(bytes) {
        if (bytes < 1024) {
            return bytes + ' B';
        }
        if (bytes < 1048576) {
            return (bytes / 1024).toFixed(0) + ' KB';
        }
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    function readFileAsDataUrl(file) {
        return new Promise(function (resolve, reject) {
            var reader = new FileReader();
            reader.onload = function () {
                resolve(reader.result);
            };
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    }

    function canvasToBlob(canvas, type, quality) {
        return new Promise(function (resolve) {
            canvas.toBlob(function (blob) {
                resolve(blob);
            }, type || 'image/jpeg', quality == null ? 0.92 : quality);
        });
    }

    function assignFile(input, file) {
        try {
            var dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            return true;
        } catch (e) {
            return false;
        }
    }

    function ImageCropUpload(root) {
        this.root = root;
        this.input = $(root, '[data-icu-input]');
        this.dropzone = $(root, '[data-icu-dropzone]');
        this.preview = $(root, '[data-icu-preview]');
        this.placeholder = $(root, '[data-icu-placeholder]');
        this.actions = $(root, '[data-icu-actions]');
        this.changeBtn = $(root, '[data-icu-change]');
        this.clearBtn = $(root, '[data-icu-clear]');
        this.aspectRatio = parseFloat(root.getAttribute('data-aspect-ratio') || '1') || 1;
        this.maxBytes = parseInt(root.getAttribute('data-max-bytes') || '2097152', 10);
        this.modalId = root.getAttribute('data-modal-id');
        this.modalEl = document.getElementById(this.modalId);
        this.cropImage = this.modalEl ? this.modalEl.querySelector('[data-icu-crop-image]') : null;
        this.zoom = this.modalEl ? this.modalEl.querySelector('[data-icu-zoom]') : null;
        this.applyBtn = this.modalEl ? this.modalEl.querySelector('[data-icu-apply]') : null;
        this.cropper = null;
        this.minZoom = 1;
        this.pendingName = 'photo.jpg';
        this.pendingSourceUrl = null;
        this.objectUrl = null;
        this.bound = false;

        this.bind();
    }

    ImageCropUpload.prototype.bind = function () {
        if (this.bound || !this.input || !this.dropzone || !this.modalEl || typeof window.Cropper === 'undefined') {
            return;
        }
        this.bound = true;

        var self = this;

        this.dropzone.addEventListener('click', function () {
            self.input.click();
        });

        this.dropzone.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                self.input.click();
            }
        });

        ['dragenter', 'dragover'].forEach(function (evt) {
            self.dropzone.addEventListener(evt, function (e) {
                e.preventDefault();
                e.stopPropagation();
                self.dropzone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach(function (evt) {
            self.dropzone.addEventListener(evt, function (e) {
                e.preventDefault();
                e.stopPropagation();
                self.dropzone.classList.remove('is-dragover');
            });
        });

        this.dropzone.addEventListener('drop', function (e) {
            var files = e.dataTransfer && e.dataTransfer.files;
            if (files && files[0]) {
                self.openWithFile(files[0]);
            }
        });

        this.input.addEventListener('change', function () {
            if (self.input.files && self.input.files[0]) {
                self.openWithFile(self.input.files[0]);
            }
        });

        if (this.changeBtn) {
            this.changeBtn.addEventListener('click', function () {
                // Re-open adjuster with current pending source, or browse for a new file.
                if (self.pendingSourceUrl) {
                    self.cropImage.src = self.pendingSourceUrl;
                    self.showModal();
                    // Init after modal is visible so Cropper measures correctly.
                    window.setTimeout(function () {
                        self.initCropper();
                    }, 180);
                } else {
                    self.input.click();
                }
            });
        }

        if (this.clearBtn) {
            this.clearBtn.addEventListener('click', function () {
                self.clear();
            });
        }

        if (this.applyBtn) {
            this.applyBtn.addEventListener('click', function () {
                self.applyCrop();
            });
        }

        if (this.zoom) {
            this.zoom.addEventListener('input', function () {
                if (!self.cropper) {
                    return;
                }
                var t = parseFloat(self.zoom.value || '0');
                var maxZoom = self.minZoom * 3;
                self.cropper.zoomTo(self.minZoom + (maxZoom - self.minZoom) * t);
            });
        }

        if (window.jQuery) {
            window.jQuery(this.modalEl).on('shown.bs.modal', function () {
                if (self.cropImage && self.cropImage.src && !self.cropper) {
                    self.initCropper();
                } else if (self.cropper) {
                    self.cropper.resize();
                }
            });
            window.jQuery(this.modalEl).on('hidden.bs.modal', function () {
                self.destroyCropper();
            });
        }
    };

    ImageCropUpload.prototype.openWithFile = function (file) {
        setError(this.root, '');

        if (!file || ACCEPTED.indexOf(file.type) === -1) {
            setError(this.root, 'Please choose a JPEG, PNG, GIF, or WebP image.');
            this.input.value = '';
            return;
        }

        if (file.size > this.maxBytes) {
            setError(this.root, 'Image is too large (max ' + formatBytes(this.maxBytes) + ').');
            this.input.value = '';
            return;
        }

        this.pendingName = (file.name || 'photo').replace(/\.[^.]+$/, '') + '.jpg';
        var self = this;

        readFileAsDataUrl(file).then(function (url) {
            self.pendingSourceUrl = url;
            self.cropImage.src = url;
            self.showModal();
            window.setTimeout(function () {
                self.initCropper();
            }, 180);
        }).catch(function () {
            setError(self.root, 'Could not read that image. Please try another file.');
            self.input.value = '';
        });
    };

    ImageCropUpload.prototype.showModal = function () {
        if (window.jQuery) {
            window.jQuery(this.modalEl).modal('show');
        } else {
            this.modalEl.classList.add('show');
            this.modalEl.style.display = 'block';
        }
    };

    ImageCropUpload.prototype.hideModal = function () {
        if (window.jQuery) {
            window.jQuery(this.modalEl).modal('hide');
        } else {
            this.modalEl.classList.remove('show');
            this.modalEl.style.display = 'none';
        }
    };

    ImageCropUpload.prototype.destroyCropper = function () {
        if (this.cropper) {
            this.cropper.destroy();
            this.cropper = null;
        }
        if (this.zoom) {
            this.zoom.value = '0';
        }
    };

    ImageCropUpload.prototype.initCropper = function () {
        this.destroyCropper();
        if (!this.cropImage || !this.cropImage.src || typeof window.Cropper === 'undefined') {
            setError(this.root, 'Photo adjuster is unavailable right now. Please refresh and try again.');
            return;
        }

        var self = this;

        // Fixed frame: user only drags/zooms the photo; crop box stays locked.
        this.cropper = new window.Cropper(this.cropImage, {
            aspectRatio: this.aspectRatio,
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 1,
            responsive: true,
            background: false,
            movable: true,
            zoomable: true,
            zoomOnWheel: true,
            scalable: false,
            rotatable: false,
            cropBoxMovable: false,
            cropBoxResizable: false,
            toggleDragModeOnDblclick: false,
            guides: false,
            center: false,
            highlight: false,
            ready: function () {
                var imageData = self.cropper.getImageData();
                self.minZoom = imageData.naturalWidth
                    ? (imageData.width / imageData.naturalWidth)
                    : 1;
                if (self.zoom) {
                    self.zoom.value = '0';
                }
                // Keep the crop box centered and full-size in the stage.
                self.cropper.setDragMode('move');
            }
        });
    };

    ImageCropUpload.prototype.applyCrop = function () {
        if (!this.cropper) {
            return;
        }

        var canvas = this.cropper.getCroppedCanvas({
            width: OUTPUT_SIZE,
            height: Math.round(OUTPUT_SIZE / this.aspectRatio),
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high'
        });

        if (!canvas) {
            setError(this.root, 'Could not prepare that photo. Please try again.');
            return;
        }

        var self = this;
        canvasToBlob(canvas, 'image/jpeg', 0.92).then(function (blob) {
            if (!blob) {
                setError(self.root, 'Could not prepare the adjusted photo.');
                return;
            }

            if (blob.size > self.maxBytes) {
                setError(self.root, 'Photo is still too large after cropping. Try zooming in more.');
                return;
            }

            var file = new File([blob], self.pendingName, { type: 'image/jpeg', lastModified: Date.now() });
            if (!assignFile(self.input, file)) {
                setError(self.root, 'Your browser could not attach the photo. Please try another browser.');
                return;
            }

            // Required attribute is satisfied via DataTransfer assignment above.
            if (self.objectUrl) {
                URL.revokeObjectURL(self.objectUrl);
            }
            self.objectUrl = URL.createObjectURL(blob);
            self.preview.src = self.objectUrl;
            show(self.preview);
            hide(self.placeholder);
            show(self.actions);
            setError(self.root, '');
            self.hideModal();
        });
    };

    ImageCropUpload.prototype.clear = function () {
        this.input.value = '';
        this.pendingSourceUrl = null;
        if (this.objectUrl) {
            URL.revokeObjectURL(this.objectUrl);
            this.objectUrl = null;
        }
        this.preview.removeAttribute('src');
        hide(this.preview);
        show(this.placeholder);
        hide(this.actions);
        setError(this.root, '');
    };

    function initAll() {
        if (typeof window.Cropper === 'undefined') {
            return;
        }
        document.querySelectorAll('[data-image-crop-upload]').forEach(function (root) {
            if (root.__icuInstance) {
                return;
            }
            root.__icuInstance = new ImageCropUpload(root);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

    window.CrmImageCropUpload = { init: initAll };
})(window, document);
