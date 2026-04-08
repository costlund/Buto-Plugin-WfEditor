function plugin_wf_embed(){
  this.expand = function(){
    var workarea = document.getElementById('modal_element_editor_body');
    var btn_child = workarea.getElementsByClassName('btn_child');
    for(var i=0;i<btn_child.length;i++){
      btn_child[i].click();
    }
  }
}
var PluginWfEmbed = new plugin_wf_embed();
