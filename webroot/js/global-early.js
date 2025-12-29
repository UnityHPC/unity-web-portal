function downloadFile(text, filename) {
  var element = document.createElement("a");
  element.setAttribute(
    "href",
    "data:text/plain;charset=utf-8," + encodeURIComponent(text),
  );
  element.setAttribute("download", filename);

  element.style.display = "none";
  $("body").append(element);
  element.click();
  element.remove();
}

function setupCKEditor(extraPlugins = []) {
  const {
    ClassicEditor, Essentials, Bold, Italic, Strikethrough, Underline, BlockQuote, Code, CodeBlock,
    Heading, HorizontalLine, Indent, Link, List, Paragraph, Undo, FontFamily, FontSize
  } = CKEDITOR;
  plugins = [
    Essentials, Bold, Italic, Strikethrough, Underline, BlockQuote, Code, CodeBlock,
    Heading, HorizontalLine, Indent, Link, List, Paragraph, Undo, FontFamily, FontSize
  ].concat(extraPlugins);
  return ClassicEditor.create(document.querySelector('#editor'), {
    licenseKey: 'GPL',
    plugins: plugins,
    toolbar: [
      'undo', 'redo',
      '|',
      'heading',
      '|',
      'fontfamily', 'fontsize',
      '|',
      'bold', 'italic', 'strikethrough', 'code',
      '|',
      'link', 'blockQuote', 'codeBlock',
      '|',
      'bulletedList', 'numberedList', 'outdent', 'indent'
    ],
  });
}
