@php
    $editorId = $editorId ?? 'store-description';
    $content = old('description', $value ?? '');
    $quillId = $editorId . '-quill';
@endphp
<div class="form-group">
    <label for="{{ $editorId }}">Description</label>
    <p class="form-hint">Rich text editor — format text, insert links, and <strong>upload images</strong> (max 10MB, JPG/PNG/WebP/GIF). Saved to <code>editor/{{ auth()->id() }}/</code>.</p>
    <textarea name="description" id="{{ $editorId }}" class="rich-editor-source" hidden>{{ $content }}</textarea>
    <div id="{{ $quillId }}" class="quill-editor-mount" data-upload-url="{{ route('editor.upload-image') }}"></div>
</div>
@once
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">
        <style>
            .quill-editor-mount {
                background: #fff;
                border-radius: 8px;
                max-width: 100%;
            }
            .quill-editor-mount .ql-editor {
                min-height: 280px;
                font-size: 1rem;
                line-height: 1.6;
            }
            .quill-editor-mount .ql-editor img {
                max-width: 100%;
                height: auto;
                border-radius: 6px;
            }
            .quill-editor-mount .ql-toolbar {
                border-radius: 8px 8px 0 0;
                border-color: var(--border, #e5e7eb);
            }
            .quill-editor-mount .ql-container {
                border-radius: 0 0 8px 8px;
                border-color: var(--border, #e5e7eb);
            }
        </style>
    @endpush
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                function quillImageHandler(quill, uploadUrl) {
                    var input = document.createElement('input');
                    input.setAttribute('type', 'file');
                    input.setAttribute('accept', 'image/jpeg,image/png,image/webp,image/gif');
                    input.click();

                    input.onchange = function () {
                        var file = input.files[0];
                        if (!file) return;

                        var formData = new FormData();
                        formData.append('image', file);

                        var range = quill.getSelection(true) || { index: quill.getLength() };

                        if (file.size > 10 * 1024 * 1024) {
                            alert('Image is too large. Maximum size is 10 MB.');
                            return;
                        }

                        fetch(uploadUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: formData,
                            credentials: 'same-origin',
                        })
                            .then(function (res) {
                                return res.json().then(function (data) {
                                    if (!res.ok) {
                                        throw new Error(data.message || 'Upload failed');
                                    }
                                    return data;
                                });
                            })
                            .then(function (data) {
                                if (!data.url) throw new Error('No image URL returned');
                                quill.insertEmbed(range.index, 'image', data.url, 'user');
                                quill.setSelection(range.index + 1);
                            })
                            .catch(function (err) {
                                alert(err.message || 'Image upload failed. Use JPG, PNG, WebP or GIF under 10MB.');
                            });
                    };
                }

                document.querySelectorAll('.quill-editor-mount').forEach(function (mount) {
                    if (mount.dataset.quillReady === '1') return;
                    mount.dataset.quillReady = '1';

                    var sourceId = mount.id.replace(/-quill$/, '');
                    var source = document.getElementById(sourceId);
                    var uploadUrl = mount.dataset.uploadUrl;
                    if (!source || !uploadUrl) return;

                    var quill = new Quill(mount, {
                        theme: 'snow',
                        modules: {
                            toolbar: {
                                container: [
                                    [{ header: [2, 3, 4, false] }],
                                    ['bold', 'italic', 'underline', 'strike'],
                                    [{ list: 'ordered' }, { list: 'bullet' }],
                                    ['blockquote', 'link', 'image'],
                                    ['clean']
                                ],
                                handlers: {
                                    image: function () {
                                        quillImageHandler(quill, uploadUrl);
                                    }
                                }
                            }
                        }
                    });

                    if (source.value.trim()) {
                        quill.root.innerHTML = source.value;
                    }

                    var form = mount.closest('form');
                    if (form) {
                        form.addEventListener('submit', function () {
                            source.value = quill.root.innerHTML;
                        });
                    }
                });
            });
        </script>
    @endpush
@endonce
