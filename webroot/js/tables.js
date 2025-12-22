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

function setColumnVisibility(stylesheet, table_id, column_index, is_visible) {
  const selectorText = `#${table_id} tr > :nth-child(${column_index})`;
  const rule = `${selectorText} { display: none !important; }`;
  if (is_visible) {
    for (let i = stylesheet.cssRules.length - 1; i >= 0; i--) {
      if (stylesheet.cssRules[i].selectorText === selectorText) {
        stylesheet.deleteRule(i);
      }
    }
  } else {
    stylesheet.insertRule(rule);
  }
}

$("table.column-toggle").each(function () {
  const table = $(this);
  const id = $(this).attr("id");
  if (typeof id === "undefined") {
    console.log("error: table does not have id attribute");
    return;
  }

  const columnToggleStyle = document.createElement('style');
  document.head.appendChild(columnToggleStyle);

  const toggleContainer = $(`<div id="columnToggle${id}" style="margin-bottom: 10px;"></div>`);
  table.before(toggleContainer);

  const headers = table.find('th').toArray();
  headers.forEach((th, index) => {
    const headerText = th.textContent.replace('⫧', '').trim();
    const label = $('<label></label>');
    const checkbox = $('<input type="checkbox" class="col-toggle" checked>');

    checkbox.on('change', function () {
      setColumnVisibility(columnToggleStyle.sheet, id, index + 1, this.checked)
    });

    label.append(checkbox);
    label.append(headerText);
    toggleContainer.append(label);
  });
});
