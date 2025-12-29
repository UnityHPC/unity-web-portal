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

function setupCKEditor() {
  const {
    ClassicEditor, Essentials, Bold, Italic, Strikethrough, Underline, BlockQuote, Code, CodeBlock,
    Heading, HorizontalLine, Indent, Link, List, Paragraph, Undo, FontFamily, FontSize
  } = CKEDITOR;
  ClassicEditor.create(document.querySelector('#editor'), {
    licenseKey: 'GPL',
    plugins: [
      Essentials, Bold, Italic, Strikethrough, Underline, BlockQuote, Code, CodeBlock,
      Heading, HorizontalLine, Indent, Link, List, Paragraph, Undo, FontFamily, FontSize
    ],
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
  }).then(editor => { mainEditor = editor; }).catch(error => { console.error(error) });
}
