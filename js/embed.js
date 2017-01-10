



function plugin_wf_embed(){
  
  this.expand = function(){
    
    var workarea = document.getElementById('wf_editor_workarea');
    var btn_child = workarea.getElementsByClassName('btn_child');
    //console.log(btn_child);
    for(var i=0;i<btn_child.length;i++){
      //console.log(btn_child[i]);
      btn_child[i].click();
    }
    
    
    
  }
  
}
var PluginWfEmbed = new plugin_wf_embed();



