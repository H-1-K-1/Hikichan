/*
 * options.js - allow users to choose board options as they wish
 *
 * Copyright (c) 2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Modified to integrate options button into mobile menu
 */

+function(){

var options_button, options_handler, options_background, options_div
  , options_close, options_tablist, options_tabs, options_current_tab;

var Options = {};
window.Options = Options;

var first_tab = function() {
  for (var i in options_tabs) {
    return i;
  }
  return false;
};

Options.show = function() {
  if (!options_current_tab) {
    Options.select_tab(first_tab(), true);
  }
  options_handler.fadeIn();
};

Options.hide = function() {
  options_handler.fadeOut();
};

options_tabs = {};

Options.add_tab = function(id, icon, name, content) {
  var tab = {};

  if (typeof content == "string") {
    content = $("<div>"+content+"</div>");
  }

  tab.id = id;
  tab.name = name;
  tab.icon = $("<div class='options_tab_icon'><i class='fa fa-"+icon+"'></i><div>"+name+"</div></div>");
  tab.content = $("<div class='options_tab'></div>").css("display", "none");

  tab.content.appendTo(options_div);

  tab.icon.on("click", function() {
    Options.select_tab(id);
  }).appendTo(options_tablist);

  $("<h2>"+name+"</h2>").appendTo(tab.content);

  if (content) {
    content.appendTo(tab.content);
  }
  
  options_tabs[id] = tab;

  return tab;
};

Options.get_tab = function(id) {
  return options_tabs[id];
};

Options.extend_tab = function(id, content) {
  if (typeof content == "string") {
    content = $("<div>"+content+"</div>");
  }

  content.appendTo(options_tabs[id].content);

  return options_tabs[id];
};

Options.select_tab = function(id, quick) {
  if (options_current_tab) {
    if (options_current_tab.id == id) {
      return false;
    }
    options_current_tab.content.fadeOut();
    options_current_tab.icon.removeClass("active");
  }
  var tab = options_tabs[id];
  options_current_tab = tab;
  options_current_tab.icon.addClass("active");
  tab.content[quick? "show" : "fadeIn"]();

  return tab;
};

options_handler = $("<div id='options_handler'></div>").css("display", "none");
options_background = $("<div id='options_background'></div>").on("click", Options.hide).appendTo(options_handler);
options_div = $("<div id='options_div'></div>").appendTo(options_handler);
options_close = $("<a id='options_close' href='javascript:void(0)'><i class='fa fa-times'></i></a>")
  .on("click", Options.hide).appendTo(options_div);
options_tablist = $("<div id='options_tablist'></div>").appendTo(options_div);

$(function(){
  options_button = $("<a href='javascript:void(0)' title='"+_("Options")+"'>["+_("Options")+"]</a>");

  // Check if mobile menu (hamburger) is active
  if ($(".cb-hamburger").length) {
    // For mobile: Add options button to mobile menu when it opens
    $(document).on("click", ".cb-hamburger", function() {
      var $mobileMenu = $(".cb-mobile-menu");
      if ($mobileMenu.length && !$mobileMenu.find(".cb-mobile-options").length) {
        var mobileOptionsButton = $("<a class='cb-mobile-item cb-mobile-options' href='javascript:void(0)'><i class='fa fa-gear'></i> "+_("Options")+"</a>")
          .on("click", Options.show);
        mobileOptionsButton.appendTo($mobileMenu);
      }
    });
  }
  else if ($(".boardlist.compact-boardlist").length) {
    // Desktop: Add gear icon to boardlist
    options_button.addClass("cb-item cb-fa cb-right").html("<i class='fa fa-gear'></i>");
    options_button.appendTo($(".boardlist:first"));
  }
  else {
    // Fallback: Prepend to body if no boardlist
    options_button.prependTo($(document.body));
  }

  // Bind click handler for non-mobile button
  options_button.on("click", Options.show);

  options_handler.appendTo($(document.body));
});

}();