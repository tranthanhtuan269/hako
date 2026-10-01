@php
    $editorId = $editorId ?? 'store-description';
    $content = old('description', $value ?? '');
@endphp
@include('partials.ckeditor-editor', [
    'value' => $content,
    'editorId' => $editorId,
    'fieldName' => 'description',
    'label' => 'Description',
    'hint' => 'Trình soạn thảo CKEditor — định dạng văn bản, liên kết, bảng, ảnh và video (YouTube, Vimeo, MP4).'
])
