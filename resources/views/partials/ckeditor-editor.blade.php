@php
    $editorId = $editorId ?? 'post-content';
    $fieldName = $fieldName ?? 'content';
    $content = old($fieldName, $value ?? '');
    $label = $label ?? 'Content';
    $hint = $hint ?? 'Trình soạn thảo CKEditor — định dạng văn bản, tiêu đề, danh sách, bảng, chèn ảnh (tối đa 10MB) và chèn video (YouTube, Vimeo, MP4).';
@endphp
<div class="form-group ckeditor-field">
    <label for="{{ $editorId }}" style="font-weight: 600;">{{ $label }}</label>
    <p class="form-hint">{!! $hint !!}</p>
    <textarea
        name="{{ $fieldName }}"
        id="{{ $editorId }}"
        class="ckeditor-source"
        rows="14"
    >{{ $content }}</textarea>
</div>

@once
    @push('styles')
        <style>
            .ckeditor-field .ck-editor {
                max-width: 100%;
            }
            .ckeditor-field .ck-editor__editable {
                min-height: 380px;
                max-height: 75vh;
                font-size: 15px;
                line-height: 1.6;
            }
            .ckeditor-field .ck-editor__editable img {
                max-width: 100%;
                height: auto;
                border-radius: 6px;
            }
            .ckeditor-field .ck-editor__editable figure.image,
            .ckeditor-field .ck-editor__editable figure.article-media {
                margin: 1.25rem auto;
                max-width: 100%;
                text-align: center;
            }
            .ckeditor-field .ck-editor__editable figure.article-media--logo img,
            .ckeditor-field .ck-editor__editable figure img[src*="/logos/"] {
                max-width: 160px;
                height: auto;
            }
            .ckeditor-field .ck-editor__editable figcaption {
                font-size: 0.85rem;
                color: #64748b;
                margin-top: 0.4rem;
                text-align: center;
            }
            .ckeditor-field .ck-editor__editable .media {
                max-width: 100%;
                margin: 1.5rem 0;
            }
            .ckeditor-field .ck-editor__editable .media iframe,
            .ckeditor-field .ck-editor__editable .media video {
                border-radius: 8px;
            }
            .ck.ck-toolbar .ck-button[data-cke-tooltip-text*="media"],
            .ck.ck-toolbar .ck-button[data-cke-tooltip-text*="Media"] {
                background: #eff6ff !important;
                color: #2563eb !important;
                border-radius: 4px;
            }
            .ck.ck-toolbar .ck-button[data-cke-tooltip-text*="media"]:hover,
            .ck.ck-toolbar .ck-button[data-cke-tooltip-text*="Media"]:hover {
                background: #dbeafe !important;
                color: #1d4ed8 !important;
            }
            .ck.ck-toolbar .ck-button[data-cke-tooltip-text*="media"] svg,
            .ck.ck-toolbar .ck-button[data-cke-tooltip-text*="Media"] svg {
                fill: #2563eb !important;
            }
        </style>
    @endpush
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/@ckeditor/ckeditor5-build-classic@43.3.1/build/ckeditor.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof ClassicEditor === 'undefined') {
                    return;
                }

                var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                var uploadUrl = @json(route('editor.upload-image'));

                function LaravelUploadAdapter(loader) {
                    this.loader = loader;
                }

                LaravelUploadAdapter.prototype.upload = function () {
                    var loader = this.loader;

                    return loader.file.then(function (file) {
                        return new Promise(function (resolve, reject) {
                            if (file.size > 10 * 1024 * 1024) {
                                reject('Ảnh quá lớn. Kích thước tối đa là 10 MB.');
                                return;
                            }

                            var formData = new FormData();
                            formData.append('image', file);

                            fetch(uploadUrl, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken || '',
                                    'Accept': 'application/json',
                                },
                                body: formData,
                                credentials: 'same-origin',
                            })
                                .then(function (response) {
                                    return response.json().then(function (data) {
                                        if (!response.ok) {
                                            throw new Error(data.message || 'Upload thất bại');
                                        }

                                        return data;
                                    });
                                })
                                .then(function (data) {
                                    if (!data.url) {
                                        throw new Error('Không nhận được URL ảnh');
                                    }

                                    resolve({ default: data.url });
                                })
                                .catch(function (error) {
                                    reject(error.message || 'Lỗi tải ảnh lên.');
                                });
                        });
                    });
                };

                LaravelUploadAdapter.prototype.abort = function () {};

                function LaravelUploadAdapterPlugin(editor) {
                    editor.plugins.get('FileRepository').createUploadAdapter = function (loader) {
                        return new LaravelUploadAdapter(loader);
                    };
                }

                var editors = [];

                document.querySelectorAll('.ckeditor-source').forEach(function (textarea) {
                    if (textarea.dataset.ckeditorReady === '1') {
                        return;
                    }

                    textarea.dataset.ckeditorReady = '1';

                    // Normalize existing HTML so CKEditor treats <figure class="article-media..."> as image block widgets
                    var initialVal = textarea.value || '';
                    if (initialVal.indexOf('<figure') !== -1) {
                        initialVal = initialVal.replace(/<figure\b([^>]*?)class=["']([^"']*)["']([^>]*?)>/gi, function (match, pre, cls, post) {
                            if (!/\bimage\b/.test(cls)) {
                                cls = ('image ' + cls).trim();
                            }
                            return '<figure' + pre + 'class="' + cls + '"' + post + '>';
                        });
                        initialVal = initialVal.replace(/<figure\b(?![^>]*\bclass=)([^>]*?)>/gi, '<figure class="image"$1>');
                        textarea.value = initialVal;
                    }

                    ClassicEditor.create(textarea, {
                        licenseKey: 'GPL',
                        extraPlugins: [LaravelUploadAdapterPlugin],
                        toolbar: {
                            items: [
                                'undo',
                                'redo',
                                '|',
                                'heading',
                                '|',
                                'bold',
                                'italic',
                                'link',
                                '|',
                                'bulletedList',
                                'numberedList',
                                '|',
                                'outdent',
                                'indent',
                                '|',
                                'uploadImage',
                                'mediaEmbed',
                                '|',
                                'blockQuote',
                                'insertTable',
                            ],
                            shouldNotGroupWhenFull: true,
                        },
                        heading: {
                            options: [
                                { model: 'paragraph', title: 'Đoạn văn (Paragraph)', class: 'ck-heading_paragraph' },
                                { model: 'heading2', view: 'h2', title: 'Tiêu đề 2 (H2)', class: 'ck-heading_heading2' },
                                { model: 'heading3', view: 'h3', title: 'Tiêu đề 3 (H3)', class: 'ck-heading_heading3' },
                                { model: 'heading4', view: 'h4', title: 'Tiêu đề 4 (H4)', class: 'ck-heading_heading4' },
                            ],
                        },
                        image: {
                            toolbar: [
                                'imageTextAlternative',
                                'toggleImageCaption',
                                '|',
                                'imageStyle:inline',
                                'imageStyle:block',
                                'imageStyle:side',
                                '|',
                                'linkImage',
                            ],
                        },
                        table: {
                            contentToolbar: [
                                'tableColumn',
                                'tableRow',
                                'mergeTableCells',
                            ],
                        },
                        link: {
                            addTargetToExternalLinks: true,
                            defaultProtocol: 'https://',
                        },
                        mediaEmbed: {
                            previewsInData: true,
                            extraProviders: [
                                {
                                    name: 'directVideo',
                                    url: /^https?:\/\/.*?\.(mp4|webm|ogg|m4v)($|\?)/i,
                                    html: function (match) {
                                        var url = match[0];
                                        return (
                                            '<div class="video-container responsive-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;max-width:100%;margin:1.5rem 0;border-radius:8px;">' +
                                                '<video controls style="position:absolute;width:100%;height:100%;top:0;left:0;border-radius:8px;">' +
                                                    '<source src="' + url + '" type="video/mp4">' +
                                                '</video>' +
                                            '</div>'
                                        );
                                    }
                                }
                            ]
                        },
                    }).then(function (editor) {
                        editors.push({ editor: editor, textarea: textarea });

                        var form = textarea.closest('form');
                        if (form && form.dataset.ckeditorSubmitBound !== '1') {
                            form.dataset.ckeditorSubmitBound = '1';
                            form.addEventListener('submit', function () {
                                editors.forEach(function (item) {
                                    var data = item.editor.getData();
                                    // Post-process to ensure article-media classes remain clean
                                    item.textarea.value = data;
                                });
                            });
                        }
                    }).catch(function (error) {
                        console.error('CKEditor failed to initialize:', error);
                    });
                });
            });
        </script>
    @endpush
@endonce
