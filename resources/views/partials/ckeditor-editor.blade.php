@php
    $editorId = $editorId ?? 'post-content';
    $fieldName = $fieldName ?? 'content';
    $content = old($fieldName, $value ?? '');
    $label = $label ?? 'Content';
    $hint = $hint ?? 'Rich text editor — headings, lists, links, tables, media embed, and image upload (max 10MB). Images save to <code>editor/' . auth()->id() . '/</code>.';
@endphp
<div class="form-group ckeditor-field">
    <label for="{{ $editorId }}">{{ $label }}</label>
    <p class="form-hint">{!! $hint !!}</p>
    <textarea
        name="{{ $fieldName }}"
        id="{{ $editorId }}"
        class="ckeditor-source"
        rows="12"
    >{{ $content }}</textarea>
</div>
@once
    @push('styles')
        <style>
            .ckeditor-field .ck-editor {
                max-width: 100%;
            }
            .ckeditor-field .ck-editor__editable {
                min-height: 360px;
                max-height: 70vh;
            }
            .ckeditor-field .ck-editor__editable img {
                max-width: 100%;
                height: auto;
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
                                reject('Image is too large. Maximum size is 10 MB.');
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
                                            throw new Error(data.message || 'Upload failed');
                                        }

                                        return data;
                                    });
                                })
                                .then(function (data) {
                                    if (!data.url) {
                                        throw new Error('No image URL returned');
                                    }

                                    resolve({ default: data.url });
                                })
                                .catch(function (error) {
                                    reject(error.message || 'Image upload failed.');
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
                                'blockQuote',
                                'insertTable',
                                'mediaEmbed',
                            ],
                            shouldNotGroupWhenFull: true,
                        },
                        heading: {
                            options: [
                                { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                                { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                                { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
                                { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' },
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
                        },
                    }).then(function (editor) {
                        editors.push(editor);

                        var form = textarea.closest('form');
                        if (form && form.dataset.ckeditorSubmitBound !== '1') {
                            form.dataset.ckeditorSubmitBound = '1';
                            form.addEventListener('submit', function () {
                                editors.forEach(function (instance) {
                                    instance.updateSourceElement();
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
