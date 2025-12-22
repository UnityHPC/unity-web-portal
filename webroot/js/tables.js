$("tr.expandable").on("click", function (e) {
  var target = $(e.target);
  if (target.is("button") || target.is("a") || target.is("input")) {
    return;
  }

  var button = $(this).find("button.btnExpand");
  button.trigger("click");
});

$("button.btnExpand").click(function () {
  var pi_wrapper = $(this).parent(); // parent column (td)
  var piRow = pi_wrapper.parent(); // parent row (tr)
  var piTree = piRow.parent(); // parent table (table)
  var gid = pi_wrapper.next().html();

  if ($(this).hasClass("btnExpanded")) {
    // already expanded
    var currentNode = piRow.nextAll().first();

    while (!currentNode.hasClass("expandable") && currentNode.length != 0) {
      var nextNode = currentNode.nextAll().first();
      currentNode.remove();
      currentNode = nextNode;
    }

    $(this).removeClass("btnExpanded");
    piRow.removeClass("expanded");
    piRow.removeClass("first");
  } else {
    $("button.btnExpanded").trigger("click");
    // not expanded
    $.ajax({
      url: ajax_url + gid,
      success: function (result) {
        piRow.after(result);
      },
      error: function (result) {
        piRow.after(result.responseText);
      },
    });

    $(this).addClass("btnExpanded");
    piRow.addClass("expanded");
    piRow.addClass("first");
  }
});

$("#tableSearch").keyup(function () {
  var value = $(this).val().toLowerCase();

  $("table.searchable tr:not(:first-child)").filter(function () {
    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
  });
});

// Column toggle functionality for tables with the "column-toggle" class
$("table.column-toggle").each(function () {
  const table = $(this);

  // Create and append a style element for this table's column visibility rules
  const columnToggleStyle = document.createElement('style');
  columnToggleStyle.id = 'columnToggleStyles';
  document.head.appendChild(columnToggleStyle);

  // Create container for column toggle checkboxes if it doesn't exist
  let toggleContainer = $('#columnToggle');
  if (toggleContainer.length === 0) {
    toggleContainer = $('<div id="columnToggle" style="margin-bottom: 10px;"></div>');
    table.before(toggleContainer);
  }

  // Extract column headers from the first row
  const headers = table.find('tr').first().find('th, td').map(function () {
    // Get text content, removing any filter symbols or extra whitespace
    return $(this).text().trim().replace(/^[⫧\s]+/, '');
  }).get();

  // Generate checkbox for each column
  headers.forEach((headerText, index) => {
    const col = index + 1;
    const label = $('<label></label>');
    const checkbox = $('<input type="checkbox" class="col-toggle" checked>');

    checkbox.on('change', function () {
      const rule = `table.column-toggle tr > :nth-child(${col}) { display: none !important; }`;
      const styles = columnToggleStyle.sheet;
      if (this.checked) {
        // Remove the hide rule when checked (show the column)
        for (let i = styles.cssRules.length - 1; i >= 0; i--) {
          if (styles.cssRules[i].selectorText === `table.column-toggle tr > :nth-child(${col})`) {
            styles.deleteRule(i);
          }
        }
      } else {
        // Add the hide rule when unchecked (hide the column)
        styles.insertRule(rule);
      }
    });

    label.append(checkbox);
    label.append(' ' + headerText);
    toggleContainer.append(label);
  });
});
