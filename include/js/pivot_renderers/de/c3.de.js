(function() {
  var callWithJQuery;

  callWithJQuery = function(pivotModule) {
    if (typeof exports === "object" && typeof module === "object") {
      return pivotModule(require("jquery"));
    } else if (typeof define === "function" && define.amd) {
      return define(["jquery"], pivotModule);
    } else {
      return pivotModule(jQuery);
    }
  };

  callWithJQuery(function($) {
    return $.pivotUtilities.locales.de.c3_renderers = {
        "Liniendiagramm": $.pivotUtilities.c3_renderers["Line Chart"],
        "Balkendiagramm": $.pivotUtilities.c3_renderers["Bar Chart"],
        "Balkendiagramm mit Stapeln": $.pivotUtilities.c3_renderers["Stacked Bar Chart"],
        "Fl&auml;chendiagramm": $.pivotUtilities.c3_renderers["Area Chart"],
        "Punktediagramm": $.pivotUtilities.c3_renderers["Scatter Chart"]
    };
  });

}).call(this);


//# sourceMappingURL=pivot.de.js.map
