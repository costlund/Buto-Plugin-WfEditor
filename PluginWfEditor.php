<?php

/**
 Theme editor. Edit pages, layouts, settings and more. 
 Get support from installed plugins.
 */
class PluginWfEditor{
  
  
  public function page_desktop(){
    
    
    if(!wfArray::get($_SESSION, 'plugin/wf/editor/activetheme')){
      $_SESSION = wfArray::set($_SESSION, 'plugin/wf/editor/activetheme', wfArray::get($GLOBALS, 'sys/theme'));
    }
    
    
    
    
    
    $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/editor/page/desktop.yml';
    $page = wfFilesystem::loadYml($filename);
    wfArray::set($GLOBALS, 'sys/layout_path', '/plugin/wf/editor/layout');
    wfDocument::mergeLayout($page);
  }
  
  
  public function page_edit(){
    $this->includePlugin();
    wfArray::set($GLOBALS, 'sys/layout_path', '/plugin/wf/editor/layout');
    $yml = wfRequest::get('yml');
    
    
    if(wfRequest::isPost()){
      $yml_content = wfRequest::get('yml_content');
      try {
        $array = sfYaml::load($yml_content);
        wfFilesystem::saveFile(wfArray::get($GLOBALS, 'sys/app_dir').'/'.$yml, $yml_content);
        $json = array('success' => true, 'alert' => array('Saved.'), 'removezzz' => array(str_replace('/', '.', $yml)));
        exit(json_encode($json));
      } catch (Exception $exc) {
        $json = array('success' => false, 'alert' => array('An error occure.'));
        exit(json_encode($json));
        //echo $exc->getTraceAsString();
      }

      //exit(json_encode($array));
      
      
    }else{
      $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/editor/page/edit.yml';
      $page = wfFilesystem::loadYml($filename);
      $yml_decode = urldecode($yml);
      //$filename = wfArray::get($GLOBALS, 'sys/theme_dir').'/'.$yml_decode;
      $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/'.$yml_decode;
      //echo $filename; exit;
      $yml_content = file_get_contents($filename);
      //$textarea = wfDocument::createHtmlElement('textarea', $yml_content, array('name' => 'yml_content', 'class' => 'form-control', 'style' => 'height:200px;'));
      //$page = wfArray::set($page, 'content/form/innerHTML/textarea', ($textarea));
      //$page = wfArray::set($page, 'content/menu/data/data/brand/lable', $yml_decode);
      $page = wfArray::set($page, 'content/menu/data/data/brand/lable', '&nbsp;');
      $page = wfArray::set($page, 'content/form/innerHTML/yml_content/innerHTML', $yml_content);
      $page = wfArray::set($page, 'content/form/innerHTML/yml/attribute/value', $yml_decode);
      //$page = wfArray::set($page, 'content/menu/data/data/navbar/navbar1/item/close/href', 'javascript:PluginWfDom.remove(\''.str_replace('/', '.', $yml_decode).'\');');
      $page = wfArray::set($page, 'content/menu/data/data/navbar/navbar1/item/close/onclick', 'PluginWfDom.remove(\''.str_replace('/', '.', $yml_decode).'\');return false;');
      $page = wfArray::set($page, 'content/menu/data/data/navbar/navbar1/item/save/id', str_replace('/', '.', $yml_decode).'_save');
      
      
      
      
      $script = wfArray::get($page, 'content/form/innerHTML/textarea_script_onkeypress/innerHTML');
      
      //echo $script;
      
      $page = wfArray::set($page, 'content/form/innerHTML/textarea_script_onkeypress/innerHTML', str_replace('_set_in_action_', str_replace('/', '.', $yml_decode).'_save', $script));
      
      
      //wfHelp::print_r($page, true);
      wfDocument::mergeLayout($page);
    }
    
    return null;
  }  
  public function page_element(){
    wfArray::set($GLOBALS, 'sys/layout_path', '/plugin/wf/editor/layout');
    $yml = wfRequest::get('yml');
    $yml = urldecode($yml);
    if(wfRequest::isPost()){
    }else{
      //echo str_replace('/', '.', $yml);
      //wfDocument::renderElement(array(wfDocument::createHtmlElement('button', 'Reload', array('onclick' => "PluginWfAjax.update('theme.wf.themeeditor.page.home.yml_body');"))));
      $onclick_add = "PluginWfBootstrapjs.modal({id: 'element_add', url: '/editor/elementadd?file=".urlencode($yml)."&key=', lable: 'Add', size: 'sm'});return false;";
      wfDocument::renderElement(array(
        wfDocument::createHtmlElement('a', 'Reload', array('class' => 'btn', 'onclick' => "PluginWfAjax.update('".str_replace('/', '.', $yml)."_body');return false;")),
        wfDocument::createHtmlElement('a', 'Add', array('class' => 'btn', 'onclick' => $onclick_add))
        ));
      wfPlugin::includeonce('wf/yml');
      wfPlugin::includeonce('wf/array');
      $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.$yml);
      $element = $this->create_elements($yml->get('content'), wfRequest::get('yml'));
      //wfHelp::yml_dump($element);
      wfDocument::renderElement(($element));
    }
    return null;
  }
  
  private function getYml($array){
    $temp = wfHelp::getYmlDump($array);
    if($temp=='null'){
      return '';
    }else{
      return $temp;
    }
  }
  
  public function page_elementview(){
    
    $key = urldecode(wfRequest::get('key'));
    $filename = urldecode(wfRequest::get('file'));
    
    wfPlugin::includeonce('wf/yml');
    //wfPlugin::includeonce('wf/array');
    $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.$filename, 'content/'.$key);
    //$yml->sort('attribute');
    
    $widget = new PluginWfYml(__DIR__.'/element/elementview.yml');
    
    if($yml->get('type')=='widget'){
      $widget->setById('element_view', 'attribute/class', 'alert alert-info');
      $widget->setById('element_view_innerhtml', 'settings/disabled', true);
      $widget->setById('element_view_attribute', 'settings/disabled', true);
      $widget->setById('element_view_list-group', 'settings/disabled', true);
      
      $widget->setById('element_view_plugin', 'innerHTML', 'Plugin: '.$yml->get('data/plugin'));
      $widget->setById('element_view_method', 'innerHTML', 'Method: '.$yml->get('data/method'));
      
      $widget->setById('element_view_data_value', 'innerHTML', "\n".$this->getYml($yml->get('data/data')));
      
    }else{
      $widget->setById('element_view_plugin', 'settings/disabled', true);
      $widget->setById('element_view_method', 'settings/disabled', true);
      $widget->setById('element_view_data', 'settings/disabled', true);
      $widget->setById('element_view_data_value', 'settings/disabled', true);
      
      $widget->setById('btn_documentation_plugin', 'settings/disabled', true);
      $widget->setById('btn_documentation_widget', 'settings/disabled', true);
    }
    
    $widget->set('view/innerHTML/type/innerHTML', $yml->get('type'));
    
    if($yml->get('settings/disabled')){
      $widget->set('view/innerHTML/type/attribute/style', $widget->get('view/innerHTML/type/attribute/style').'text-decoration: line-through;');
    }
    
    $widget->setById('element_view_settings_value', 'innerHTML', "\n".$this->getYml($yml->get('settings')));


    $widget->setById('btn_documentation_plugin', 'attribute/onclick', "PluginWfBootstrapjs.modal({id: 'wf_editor_pluginview', url: '/editor/pluginview?plugin=".urlencode($yml->get('data/plugin'))."', lable: 'Plugin', 'size': 'lg'});return false;");
    $widget->setById('btn_documentation_widget', 'attribute/onclick', "PluginWfBootstrapjs.modal({id: 'wf_editor_methodview', url: '/editor/methodview?plugin=".urlencode($yml->get('data/plugin'))."&method=widget_".urlencode($yml->get('data/method'))."', lable: 'Method', 'size': 'lg'});return false;");

    
    //$widget->set('view/innerHTML/key/innerHTML', "Path to key: $key");
    $onclick = "PluginWfBootstrapjs.modal({id: 'element_html', url: '/editor/elementkey?file=".urlencode($filename)."&key=".urlencode($key)."', lable: 'Key', size: 'lg'});return false;";
    $widget->set('view/innerHTML/key/innerHTML', "<a href=\"#\" onclick=\"$onclick\">Path to key</a>: $key");
    
    //$widget->set('btn_delete/attribute/data_file', urldecode(wfRequest::get('file')));
    //$widget->set('btn_delete/attribute/data_key', $key);
    
    $widget->setById('btn_delete', 'attribute/data_file', urldecode(wfRequest::get('file')));
    $widget->setById('btn_delete', 'attribute/data_key', $key);
    
    //$widget->set('btn_move/attribute/data_file', urldecode(wfRequest::get('file')));
    //$widget->set('btn_move/attribute/data_key', $key);
    
    $widget->setById('btn_move', 'attribute/data_file', urldecode(wfRequest::get('file')));
    $widget->setById('btn_move', 'attribute/data_key', $key);
    $widget->setById('btn_copy', 'attribute/data_file', urldecode(wfRequest::get('file')));
    $widget->setById('btn_copy', 'attribute/data_key', $key);
    
    $widget->setById('btn_position_up', 'attribute/data_file', urldecode(wfRequest::get('file')));
    $widget->setById('btn_position_up', 'attribute/data_key', $key);
    $widget->setById('btn_position_down', 'attribute/data_file', urldecode(wfRequest::get('file')));
    $widget->setById('btn_position_down', 'attribute/data_key', $key);
    
    
    if(is_array($yml->get('innerHTML'))){
      $widget->set('view/innerHTML/inner_html/innerHTML', "HTML: (child)");
    }else{
      $onclick = "PluginWfBootstrapjs.modal({id: 'element_html', url: '/editor/elementhtml?file=".urlencode($filename)."&key=".urlencode($key)."', lable: 'HTML', size: 'lg'});return false;";
      $widget->set('view/innerHTML/inner_html/innerHTML', "<a href=\"#\" onclick=\"$onclick\">HTML</a>: ".htmlentities($yml->get('innerHTML')));
    }
    $widget->set('view/innerHTML/listgroup/innerHTML', null);
    /**
     * Attribute.
     */
    if($yml->get('attribute')){
      $temp = $yml->get('attribute');
      ksort($temp);
      foreach ($temp as $k => $value) {
        $onclick = "PluginWfBootstrapjs.modal({id: 'element_attribute', url: '/editor/elementattribute?file=".urlencode($filename)."&key=".urlencode($key)."&attribute=".$k."', lable: 'Attribute', size: 'sm'});return false;";
        $widget->set('view/innerHTML/listgroup/innerHTML/', wfDocument::createHtmlElement('a', "$k: $value", array('class' => 'list-group-item', 'onclick' => $onclick)));
      }
    }
    $onclick = "PluginWfBootstrapjs.modal({id: 'element_attribute', url: '/editor/elementattribute?file=".urlencode($filename)."&key=".urlencode($key)."&attribute=', lable: 'Attribute', size: 'sm'});return false;";
    $widget->set('view/innerHTML/listgroup/innerHTML/', wfDocument::createHtmlElement('a', "Add", array('class' => 'list-group-item list-group-item-warning', 'onclick' => $onclick)));
    
    //wfHelp::yml_dump($widget);
    //echo 'key: '.$key.'<br>';
    //echo 'filename: '.$filename.'<br>';

    /**
     * Settings.
     */
    $onclick = "PluginWfBootstrapjs.modal({id: 'element_settings', url: '/editor/elementsettings?file=".urlencode($filename)."&key=".urlencode($key)."', lable: 'Settings', size: 'lg'});return false;";
    $widget->set('view/innerHTML/inner_settings/innerHTML', "<a href=\"#\" onclick=\"$onclick\">Settings</a>:");

    /**
     * Data.
     */
    $onclick = "PluginWfBootstrapjs.modal({id: 'element_data', url: '/editor/elementdata?file=".urlencode($filename)."&key=".urlencode($key)."', lable: 'Data', size: 'lg'});return false;";
    $widget->set('view/innerHTML/inner_data/innerHTML', "<a href=\"#\" onclick=\"$onclick\">Data</a>:");
    
    wfDocument::renderElement($widget->get());
    
  }
  
  
  public function page_elementattribute(){
    wfPlugin::includeonce('wf/yml');
    //$widget = new PluginWfYml(__DIR__.'/page/attribute.yml');
    $form = new PluginWfYml(__DIR__.'/form/attribute.yml');
    $form->set('items/attribute_origin/default', wfRequest::get('attribute'));
    $form->set('items/attribute/default', wfRequest::get('attribute'));
    $form->set('items/file/default', urldecode(wfRequest::get('file')));
    $form->set('items/key/default', urldecode(wfRequest::get('key')));
    $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.urldecode(wfRequest::get('file')), 'content/'.urldecode(wfRequest::get('key').'/attribute/'.urldecode(wfRequest::get('attribute'))));
    $form->set('items/value/default', $yml->get());
    $element = wfDocument::createWidget('wf/form', 'render', $form->get());
    wfDocument::renderElement(array($element));
    return null;
  }
  
  public function page_elementhtml(){
    $this->includePlugin();
    wfPlugin::includeonce('wf/yml');
    $form = new PluginWfYml(__DIR__.'/form/html.yml');
    $form->set('items/file/default', urldecode(wfRequest::get('file')));
    $form->set('items/key/default', urldecode(wfRequest::get('key')));
    $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.urldecode(wfRequest::get('file')), 'content/'.urldecode(wfRequest::get('key')).'/innerHTML');
    $form->set('items/html/default', $yml->get());
    $element = wfDocument::createWidget('wf/form', 'render', $form->get());
    wfDocument::renderElement(array($element));
    return null;
  }
  
  private function includePlugin(){
    $GLOBALS['sys']['settings']['plugin']['wf']['form']['enabled'] = 'true';
    $GLOBALS['sys']['settings']['plugin']['wf']['bootstrap']['enabled'] = 'true';
  }
  public function page_elementsettings(){
    $this->includePlugin();
    wfPlugin::includeonce('wf/yml');
    $form = new PluginWfYml(__DIR__.'/form/settings.yml');
    $form->set('items/file/default', urldecode(wfRequest::get('file')));
    $form->set('items/key/default', urldecode(wfRequest::get('key')));
    $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.urldecode(wfRequest::get('file')), 'content/'.urldecode(wfRequest::get('key')).'/settings');
    $form->set('items/settings/default', wfHelp::getYmlDump($yml->get()));
    $element = wfDocument::createWidget('wf/form', 'render', $form->get());
    $script = wfDocument::createHtmlElement('script', "PluginWfTextareatab.setTextareaTabEnabled(document.getElementById('frm_element_settings_settings'), '  ');");
    wfDocument::renderElement(array($element, $script));
    return null;
  }
  
  public function page_elementdata(){
    $this->includePlugin();
    wfPlugin::includeonce('wf/yml');
    $form = new PluginWfYml(__DIR__.'/form/data.yml');
    $form->set('items/file/default', urldecode(wfRequest::get('file')));
    $form->set('items/key/default', urldecode(wfRequest::get('key')));
    $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.urldecode(wfRequest::get('file')), 'content/'.urldecode(wfRequest::get('key')).'/data/data');
    $form->set('items/data/default', wfHelp::getYmlDump($yml->get()));
    $element = wfDocument::createWidget('wf/form', 'render', $form->get());
    $script = wfDocument::createHtmlElement('script', "PluginWfTextareatab.setTextareaTabEnabled(document.getElementById('frm_element_data_data'), '  ');");
    wfDocument::renderElement(array($element, $script));
    return null;
  }
  
  
  /**
   * Handle move in a session param.
   * @param type $action
   * @param type $copy If action is "set" and we like to copy.
   * @return type
   */
  private function handle_move_param($action, $copy = false){
    if($action == 'set'){
      $_SESSION['plugin']['wf']['editor']['move']['copy'] = $copy;
      $_SESSION['plugin']['wf']['editor']['move']['file'] = urldecode(wfRequest::get('file'));
      $_SESSION['plugin']['wf']['editor']['move']['key'] = urldecode(wfRequest::get('key'));
      return null;
    }elseif($action == 'unset'){
      unset($_SESSION['plugin']['wf']['editor']['move']);
      return null;
    }elseif($action == 'get'){
      if(isset($_SESSION['plugin']['wf']['editor']['move']['file']) && isset($_SESSION['plugin']['wf']['editor']['move']['key'])){
        return array('file' => $_SESSION['plugin']['wf']['editor']['move']['file'], 'key' => $_SESSION['plugin']['wf']['editor']['move']['key'], 'copy' => $_SESSION['plugin']['wf']['editor']['move']['copy']);
      }else{
        return null;
      }
    }elseif($action == 'copy'){
      if(isset($_SESSION['plugin']['wf']['editor']['move']['copy']) && $_SESSION['plugin']['wf']['editor']['move']['copy']){
        return true;
      }else{
        return false;
      }
    }
  }
  
  public function page_elementadd(){
    $this->includePlugin();
    wfPlugin::includeonce('wf/yml');
    $element = new PluginWfYml(__DIR__.'/page/elementadd.yml');
    $onclick = "PluginWfBootstrapjs.modal({id: 'element_add_html', url: '/editor/elementaddhtml?file=".(wfRequest::get('file'))."&key=".(wfRequest::get('key'))."', lable: 'Add HTML element', size: 'sm'});";
    $onclick .= "document.getElementById('element_add_btn_close').click();";
    $onclick .= "return false;";
    $element->set('group/innerHTML/btn_html_element/attribute/onclick', $onclick);
    $onclick = "PluginWfBootstrapjs.modal({id: 'element_add_html_object', url: '/editor/elementaddhtmlobject?file=".(wfRequest::get('file'))."&key=".(wfRequest::get('key'))."', lable: 'Add HTML element', size: 'sm'});";
    $onclick .= "document.getElementById('element_add_btn_close').click();";
    $onclick .= "return false;";
    $element->set('group/innerHTML/btn_html_object_element/attribute/onclick', $onclick);
    
    //$onclick = "PluginWfBootstrapjs.modal({id: 'element_add_widget', url: '/editor/elementaddwidget?file=".(wfRequest::get('file'))."&key=".(wfRequest::get('key'))."', lable: 'Add Widget', size: 'sm'});";
    $onclick = "PluginWfBootstrapjs.modal({id: 'modal_plugin', url: '/editor/plugin', lable: 'Plugin', size: 'lg'});";
    //$onclick .= "document.getElementById('element_add_btn_close').click();";
    $onclick .= "return false;";
    $element->set('group/innerHTML/btn_widget/attribute/onclick', $onclick);

    
//    $widget->set('btn_widget_add_from_window/attribute/data_file', urldecode(wfRequest::get('file')));
//    $widget->set('btn_widget_add_from_window/attribute/data_key', $key);
    $element->setById('btn_widget_add_from_window', 'attribute/data_file', urldecode(wfRequest::get('file')));
    $element->setById('btn_widget_add_from_window', 'attribute/data_key', urldecode(wfRequest::get('key')));
    
    
    if($this->handle_move_param('get')){
      if($this->handle_move_param('copy')){
        $onclick = "$.get('/editor/action?a=copy_do&file=".(wfRequest::get('file'))."&key=".(wfRequest::get('key'))."', function(data){PluginWfCallbackjson.call( data );});return false;";
      }else{
        $onclick = "$.get('/editor/action?a=move_do&file=".(wfRequest::get('file'))."&key=".(wfRequest::get('key'))."', function(data){PluginWfCallbackjson.call( data );});return false;";
      }
      $element->set('info_move/innerHTML/btn_move/attribute/onclick', $onclick);
      $element->set('info_move/settings/disabled', false);
      $element->set('info_move/innerHTML/file/innerHTML/value/innerHTML', urldecode(wfRequest::get('file')));
      $element->set('info_move/innerHTML/key/innerHTML/value/innerHTML', urldecode(wfRequest::get('key')));
      
      
      //wfHelp::yml_dump($this->handle_move_param('copy'));
      if($this->handle_move_param('copy')){
        $element->setById('info_move_h4', 'innerHTML', 'Copy element');
        $element->setById('info_move_btn', 'innerHTML', 'Copy');
      }
      
    }
    wfDocument::renderElement(($element->get()));
  }
  public function page_elementaddhtml(){
    $this->includePlugin();
    wfPlugin::includeonce('wf/yml');
    $form = new PluginWfYml(__DIR__.'/form/addhtml.yml');
    $form->set('items/file/default', urldecode(wfRequest::get('file')));
    $form->set('items/key/default', urldecode(wfRequest::get('key')));
    $element = wfDocument::createWidget('wf/form', 'render', $form->get());
    wfDocument::renderElement(array($element));
  }
  
  
  public function page_elementaddhtmlobject(){
    $this->includePlugin();
    wfPlugin::includeonce('wf/yml');
    $form = new PluginWfYml(__DIR__.'/form/addhtmlobject.yml');
    $form->set('items/file/default', urldecode(wfRequest::get('file')));
    $form->set('items/key/default', urldecode(wfRequest::get('key')));
    /**
     * Set options.
     */
    $option = array('' => '-');
    $dir = __DIR__.'/html_object';
    $files = wfFilesystem::getScandir(__DIR__.'/html_object');
    foreach ($files as $key => $value) {
      $yml = new PluginWfYml($dir.'/'.$value);
      $option[$key] = $yml->get('settings/name');
    }
    $form->set('items/html_object/option', $option);
    /**/
    $element = wfDocument::createWidget('wf/form', 'render', $form->get());
    wfDocument::renderElement(array($element));
  }
  
  
  public function page_elementaddwidget(){
    wfPlugin::includeonce('wf/yml');
    $form = new PluginWfYml(__DIR__.'/form/addwidget.yml');
    $form->set('items/file/default', urldecode(wfRequest::get('file')));
    $form->set('items/key/default', urldecode(wfRequest::get('key')));
    
//    $plugin_dir = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin';
//    $organisation = wfFilesystem::getScandir($plugin_dir);
//    $widgets = array();
//    foreach ($organisation as $key => $value) {
//      if(substr($value, 0, 1)=='.'){continue;}
//      $plugin = wfFilesystem::getScandir($plugin_dir.'/'.$value);
//      echo $value.'<br>';
//      wfHelp::yml_dump($plugin);
//    }
    
    /**
     * Set options.
     */
    $option = array('' => '-');
    $dir = __DIR__.'/html_object';
    $files = wfFilesystem::getScandir(__DIR__.'/html_object');
    foreach ($files as $key => $value) {
      $yml = new PluginWfYml($dir.'/'.$value);
      $option[$key] = $yml->get('settings/name');
    }
    $form->set('items/html_object/option', $option);
    /**/
    $element = wfDocument::createWidget('wf/form', 'render', $form->get());
    wfDocument::renderElement(array($element));
  }
  
  /**
   * User can´t change attribute to an already existing one.
   * @param type $field
   * @param type $form
   * @param type $data
   * @return type
   */
  public function validate_origin($field, $form, $data = array()){
    if(wfArray::get($form, "items/$field/post_value") != wfArray::get($form, "items/attribute_origin/post_value")){
      $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.urldecode(wfRequest::get('file')), 'content/'.urldecode(wfRequest::get('key').'/attribute/'.urldecode(wfRequest::get('attribute'))));
      if($yml->get()){
        $form = wfArray::set($form, "items/$field/is_valid", false);
        $form = wfArray::set($form, "items/$field/errors/", __('?lable can not be changed to an already existing attribute!', array('?lable' => wfArray::get($form, "items/$field/lable"))));
      }
    }
    return $form;
  }

  private function delete_element($file, $key){
    $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.$file, 'content');
    $yml->setUnset($key);
    $yml->save();
    /**
     * If parent is an empty array we has to set it to an empty string.
     */
    $parent_key = dirname($key);
    if($parent_key != '.' && sizeof($yml->get($parent_key)) == 0){
      $yml->set($parent_key, '');
      $yml->save();
    }
  }
  
  public function page_action(){
    wfPlugin::includeonce('wf/yml');
    wfPlugin::includeonce('wf/array');
    wfPlugin::includeonce('wf/form');
    $a = wfRequest::get('a');
    $json = new PluginWfArray();
    $json->set('success', false);
    
    if($a=='delete'){
//      $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.urldecode(wfRequest::get('file')), 'content');
//      $yml->setUnset(urldecode(wfRequest::get('key')));
//      $yml->save();
//      /**
//       * If parent is an empty array we has to set it to an empty string.
//       */
//      $parent_key = dirname(urldecode(wfRequest::get('key')));
//      if($parent_key != '.' && sizeof($yml->get($parent_key)) == 0){
//        $yml->set($parent_key, '');
//        $yml->save();
//      }
      $this->delete_element(urldecode(wfRequest::get('file')), urldecode(wfRequest::get('key')));
      $json->set('script', array("PluginWfAjax.update('".$this->file_to_id(urldecode(wfRequest::get('file')))."');", "document.getElementById('element_view_btn_close').click();"));
    }elseif($a=='move_set'){
      $this->handle_move_param('set');
      $json->set('script', array("document.getElementById('element_view_btn_close').click();"));
    }elseif($a=='copy_set'){
      $this->handle_move_param('set', true);
      $json->set('script', array("document.getElementById('element_view_btn_close').click();"));
    }elseif($a=='move_do' || $a=='copy_do'){
      /**
       * Move element.
       */
      if($this->handle_move_param('get')){
        $value = $this->handle_move_param('get');
        $move = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.$value['file'], 'content/'.$value['key']);
        //wfHelp::yml_dump($value, true);
        if(strlen(wfRequest::get('key'))){
          $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.urldecode(wfRequest::get('file')), 'content/'.urldecode(wfRequest::get('key')).'/innerHTML');
        }else{
          $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.urldecode(wfRequest::get('file')), 'content');
        }
        if(is_array($yml->get())){
          $temp = array_merge($yml->get(), array($move->get()));
          $yml->set(null, $temp);
        }else{
          $yml->set(null, array($move->get()));
        }
        $yml->save();
        if($a=='move_do'){
          $this->delete_element($value['file'], $value['key']);
          $this->handle_move_param('unset');
        }else{
        }
        $json->set('script', array("PluginWfAjax.update('".$this->file_to_id(urldecode(wfRequest::get('file')))."');", "document.getElementById('element_add_btn_close').click();"));
      }
    }elseif($a=='move_discard'){
      $this->handle_move_param('unset');
      $json->set('script', array("if(typeof PluginWfAjax == 'object'){PluginWfAjax.update('element_add_body');}"));
    }elseif($a=='attributesave'){
      $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.urldecode(wfRequest::get('file')), 'content/'.urldecode(wfRequest::get('key').'/attribute/'.urldecode(wfRequest::get('attribute'))));
      $form = new PluginWfYml(__DIR__.'/form/attribute.yml');
      $form->set(null, PluginWfForm::bindAndValidate($form->get()));
      if($form->get('is_valid')){
        $yml->set(null, $form->get('items/value/post_value'));
        $yml->save();
        /**
         * If attribute name is changed.
         */
        if($form->get("items/attribute/post_value") != $form->get("items/attribute_origin/post_value")){
          $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.urldecode(wfRequest::get('file')), 'content/'.urldecode(wfRequest::get('key').'/attribute'));
          $yml->setUnset(urldecode(wfRequest::get('attribute_origin')));
          $yml->save();
        }
        $json->set('script', array("PluginWfAjax.update('".$this->file_to_id(urldecode(wfRequest::get('file')))."');", "PluginWfAjax.update('element_view_body');", "document.getElementById('element_attribute_btn_close').click();"));
      }else{
        $json->set('script', array("alert(\"".PluginWfForm::getErrors($form->get(), "\\n")."\");"));
      }
    }elseif($a=='attribute_delete'){
      $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.urldecode(wfRequest::get('file')), 'content/'.urldecode(wfRequest::get('key')));
      $yml->setUnset('attribute/'.urldecode(wfRequest::get('attribute')));
      $yml->save();
      $json->set('script', array("PluginWfAjax.update('".$this->file_to_id(urldecode(wfRequest::get('file')))."');", "PluginWfAjax.update('element_view_body');", "document.getElementById('element_attribute_btn_close').click();"));
    }elseif($a=='htmlsave'){
      $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.urldecode(wfRequest::get('file')), 'content/'.urldecode(wfRequest::get('key').'/innerHTML'));
      $form = new PluginWfYml(__DIR__.'/form/html.yml');
      $form->set(null, PluginWfForm::bindAndValidate($form->get()));
      if($form->get('is_valid')){
        $yml->set(null, $form->get('items/html/post_value'));
        $yml->save();
        $json->set('script', array("PluginWfAjax.update('".$this->file_to_id(urldecode(wfRequest::get('file')))."');", "PluginWfAjax.update('element_view_body');", "document.getElementById('element_html_btn_close').click();"));
      }else{
        $json->set('script', array("alert(\"".PluginWfForm::getErrors($form->get(), "\\n")."\");"));
      }
    }elseif($a=='settingssave'){
      $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.urldecode(wfRequest::get('file')), 'content/'.urldecode(wfRequest::get('key').'/settings'));
      $form = new PluginWfYml(__DIR__.'/form/settings.yml');
      $form->set(null, PluginWfForm::bindAndValidate($form->get()));
      if($form->get('is_valid')){
        $value = $form->get('items/settings/post_value');
        try {
          $value = sfYaml::load($value);
          $yml->set(null, $value);
          $yml->save();
          $json->set('script', array("PluginWfAjax.update('".$this->file_to_id(urldecode(wfRequest::get('file')))."');", "PluginWfAjax.update('element_view_body');", "document.getElementById('element_settings_btn_close').click();"));
        } catch (Exception $exc) {
          $json->set('script', array("alert(\"Unable to parse yml!\");"));
        }
      }else{
        $json->set('script', array("alert(\"".PluginWfForm::getErrors($form->get(), "\\n")."\");"));
      }
    }elseif($a=='datasave'){
      $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.urldecode(wfRequest::get('file')), 'content/'.urldecode(wfRequest::get('key').'/data/data'));
      $form = new PluginWfYml(__DIR__.'/form/data.yml');
      $form->set(null, PluginWfForm::bindAndValidate($form->get()));
      if($form->get('is_valid')){
        $value = $form->get('items/data/post_value');
        try {
          $value = sfYaml::load($value);
          $yml->set(null, $value);
          $yml->save();
          $json->set('script', array("PluginWfAjax.update('".$this->file_to_id(urldecode(wfRequest::get('file')))."');", "PluginWfAjax.update('element_view_body');", "document.getElementById('element_data_btn_close').click();"));
        } catch (Exception $exc) {
          $json->set('script', array("alert(\"Unable to parse yml!\");"));
        }
      }else{
        $json->set('script', array("alert(\"".PluginWfForm::getErrors($form->get(), "\\n")."\");"));
      }
    }elseif($a=='addhtmlsave'){
      if(wfRequest::get('key')){
        $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.(wfRequest::get('file')), 'content/'.wfRequest::get('key').'/innerHTML');
        //$yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.(wfRequest::get('file')), 'content/'.wfRequest::get('key').'/innerHTML');
      }else{
        $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.(wfRequest::get('file')), 'content');
        //$yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.(wfRequest::get('file')));
      }
      $form = new PluginWfYml(__DIR__.'/form/addhtml.yml');
      $form->set(null, PluginWfForm::bindAndValidate($form->get()));
      if($form->get('is_valid')){
        $inner_html = null;
        if($form->get('items/inner_html/post_value')){
          $inner_html = $form->get('items/inner_html/post_value');
        }
        $attribute = array();
        if($form->get('items/class/post_value')){
          $attribute['class'] = $form->get('items/class/post_value');
        }
        
        $element = wfDocument::createHtmlElement($form->get('items/html_tag/post_value'), $inner_html, $attribute);
        if($form->get('items/id/post_value')){
          //$yml->set($form->get('items/id/post_value').'/type', $form->get('items/html_tag/post_value'));
          $yml->set($form->get('items/id/post_value'), $element);
        }else{
          //$yml->set('/type', $form->get('items/html_tag/post_value'));
          $id = wfCrypt::getUid();
          $yml->set($id, $element);
        }
        $yml->save();
        $json->set('script', array("PluginWfAjax.update('".$this->file_to_id(urldecode(wfRequest::get('file')))."');", "document.getElementById('element_add_html_btn_close').click();"));
      }else{
        $json->set('script', array("alert(\"".PluginWfForm::getErrors($form->get(), "\\n")."\");"));
      }
    }elseif($a=='addwidget'){
      
      
      if(wfRequest::get('key')){
        $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.urldecode(wfRequest::get('file')), 'content/'.urldecode(wfRequest::get('key')).'');
      }else{
        $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.urldecode(wfRequest::get('file')));
      }
      
      //wfHelp::yml_dump($yml, true);
      
      //$data = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/'.urldecode(wfRequest::get('plugin')).'/widget/'.urldecode(wfRequest::get('widget')).'.default.yml');
      $data = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/'.urldecode(wfRequest::get('plugin')).'/default/widget.'.urldecode(wfRequest::get('widget')).'.yml');
      if($data->get()){
        $widget = wfDocument::createWidget(urldecode(wfRequest::get('plugin')), urldecode(wfRequest::get('widget')), $data->get());
      }else{
        $widget = wfDocument::createWidget(urldecode(wfRequest::get('plugin')), urldecode(wfRequest::get('widget')));
      }
      
      
      
      //wfHelp::yml_dump($widget, true);

      if(wfRequest::get('key')){
        $yml->set('innerHTML/', $widget);
      }else{
        $yml->set('content/', $widget);
      }
      $yml->save();
      
      $json->set('script', array("PluginWfAjax.update('".$this->file_to_id(urldecode(wfRequest::get('file')))."');", "$('.modal').modal('hide');"));
      //$json->set('script', array("alert(\"sdf\");"));
      
    }elseif($a=='addhtmlobjectsave'){
      if(wfRequest::get('key')){
        $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.(wfRequest::get('file')), 'content/'.wfRequest::get('key').'');
      }else{
        $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.(wfRequest::get('file')));
      }
      //wfHelp::yml_dump($yml);
      $form = new PluginWfYml(__DIR__.'/form/addhtmlobject.yml');
      $form->set(null, PluginWfForm::bindAndValidate($form->get()));
      if($form->get('is_valid')){
        
        
        $dir = __DIR__.'/html_object';
        $files = wfFilesystem::getScandir(__DIR__.'/html_object');
        $object = null;
        foreach ($files as $key => $value) {
          if($key == $form->get('items/html_object/post_value')){
            $object = new PluginWfYml($dir.'/'.$value);
            break;
          }
        }
        if($object){
          //wfHelp::yml_dump($object->get('content/0'));
          if($form->get('items/id/post_value')){
            //$yml->set($form->get('items/id/post_value').'/type', $form->get('items/html_tag/post_value'));
            if(wfRequest::get('key')){
              $yml->set('innerHTML/'.$form->get('items/id/post_value'), $object->get('content/0'));
            }else{
              $yml->set('content/'.$form->get('items/id/post_value'), $object->get('content/0'));
            }
          }else{
            //$yml->set('/type', $form->get('items/html_tag/post_value'));
            if(wfRequest::get('key')){
              $yml->set('innerHTML/', $object->get('content/0'));
            }else{
              $yml->set('content/', $object->get('content/0'));
            }
          }
        }
        
//        wfHelp::yml_dump($yml);
//        wfHelp::yml_dump($object);
//        exit($form->get('items/html_object/post_value'));
        
        $yml->save();
        $json->set('script', array("PluginWfAjax.update('".$this->file_to_id(urldecode(wfRequest::get('file')))."');", "document.getElementById('element_add_html_object_btn_close').click();"));
      }else{
        $json->set('script', array("alert(\"".PluginWfForm::getErrors($form->get(), "\\n")."\");"));
      }
    }elseif($a=='position_up' || $a=='position_down'){
      $parent_key = dirname(urldecode(wfRequest::get('key')));
      if($parent_key=='.'){
        $parent_key = 'content';
      }else{
        $parent_key = 'content/'.$parent_key;
      }
      $yml_parent = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.urldecode(wfRequest::get('file')), $parent_key);
      if($yml_parent){
        $direction = -1;
        if($a=='position_down'){
          $direction = 1;
        }
        $new_position = null;
        $id = $this->getIdFromPath(urldecode(wfRequest::get('key')));
        $items = sizeof($yml_parent->get());
        $position = 1;
        foreach ($yml_parent->get() as $key => $value) {
          if($key.''==$id){
            break;
          }
          $position++;
        }
        $new_position = $position+$direction;
        if($new_position > $items){
          $json->set('script', array("alert('Already last!');"));
        }elseif($new_position < 1){
          $json->set('script', array("alert('Already first!');"));
        }else{
          $new = $this->move_to_position($yml_parent->get(), $id, $new_position);
          if($new){
            $yml_parent->set(null, $new);
            //wfHelp::yml_dump($yml_parent);
            $yml_parent->save();
          }
          $json->set('script', array("PluginWfAjax.update('".$this->file_to_id(urldecode(wfRequest::get('file')))."');"));
        }
      }
    }elseif($a=='file_edit'){
      $filename_old = wfRequest::get('filename_old');
      $filename_new = wfRequest::get('filename_new');
      
      //exit();
      $activetheme = wfArray::get($_SESSION, 'plugin/wf/editor/activetheme');

          
      if($filename_old != $filename_new){
        $filename_old = 'theme/'.$activetheme.'/'.$filename_old;
        $filename_new = 'theme/'.$activetheme.'/'.$filename_new;
        
        if(!wfFilesystem::fileExist(wfArray::get($GLOBALS, 'sys/app_dir').'/'.dirname($filename_new))){
          $json->set('script', array("alert('Dir does not exist!');"));
        }elseif(wfFilesystem::fileExist(wfArray::get($GLOBALS, 'sys/app_dir').'/'.$filename_new)){
          $json->set('script', array("alert('File already exist!');"));
        }else{
          if(wfRequest::get('copy') != 'on'){
            if(rename(wfArray::get($GLOBALS, 'sys/app_dir').'/'.$filename_old, wfArray::get($GLOBALS, 'sys/app_dir').'/'.$filename_new)){
              $json->set('script', array("PluginWfAjax.update('modal_files_body');", "document.getElementById('modal_file_edit_btn_close').click();"));
            }else{
            $json->set('script', array("alert('Could not rename file!');"));
            }
          }else{
            if(copy(wfArray::get($GLOBALS, 'sys/app_dir').'/'.$filename_old, wfArray::get($GLOBALS, 'sys/app_dir').'/'.$filename_new)){
              $json->set('script', array("PluginWfAjax.update('modal_files_body');", "document.getElementById('modal_file_edit_btn_close').click();"));
            }else{
              $json->set('script', array("alert('Could not copy file!');"));
            }
          }
        }
      }else{
        $json->set('script', array("document.getElementById('modal_file_edit_btn_close').click();"));
      }
    }elseif($a=='file_delete'){
      $file = urldecode(wfRequest::get('file'));
      if(!wfFilesystem::fileExist(wfArray::get($GLOBALS, 'sys/app_dir').'/'.$file)){
        $json->set('script', array("alert('File does not exist!');"));
      }else{
        unlink(wfArray::get($GLOBALS, 'sys/app_dir').'/'.$file);
        $json->set('script', array("PluginWfAjax.update('modal_files_body');"));
      }
    }elseif($a=='folder_delete'){
      $activetheme = wfArray::get($_SESSION, 'plugin/wf/editor/activetheme');
      $folder = urldecode(wfRequest::get('folder'));
      if(!wfFilesystem::fileExist(wfArray::get($GLOBALS, 'sys/app_dir').'/theme/'.$activetheme.'/'. $folder)){
        $json->set('script', array("alert('Folder does not exist!');"));
      }else{
        
        $check = wfFilesystem::getScandir(wfArray::get($GLOBALS, 'sys/app_dir').'/theme/'.$activetheme.'/'. $folder);
        //wfHelp::yml_dump( sizeof($check) , true);
        if(sizeof($check) == 0) {
          rmdir(wfArray::get($GLOBALS, 'sys/app_dir').'/theme/'.$activetheme.'/'. $folder);
          $json->set('script', array("PluginWfAjax.load('modal_files_body', '/editor/files');"));
        } else {
          $json->set('script', array("alert('Could not delete folder, maybe it contain files or folders!');"));
        }



      }
    }elseif($a=='file_new'){
      $activetheme = wfArray::get($_SESSION, 'plugin/wf/editor/activetheme');
      $folder = 'theme/'.$activetheme.'/'.wfRequest::get('folder');
      $filename_new = $folder.'/'.wfRequest::get('filename_new');
      $form = new PluginWfYml(__DIR__.'/form/file_new.yml');
      $form->set(null, PluginWfForm::bindAndValidate($form->get()));
      if(!$form->get('is_valid')){
        $json->set('script', array("alert(\"".PluginWfForm::getErrors($form->get(), "\\n")."\");"));
      }elseif(!wfFilesystem::fileExist(wfArray::get($GLOBALS, 'sys/app_dir').'/'.($folder))){
        $json->set('script', array("alert('Dir does not exist!');"));
      }elseif(wfFilesystem::fileExist(wfArray::get($GLOBALS, 'sys/app_dir').'/'.$filename_new)){
        $json->set('script', array("alert('File already exist!');"));
      }else{
        $content = null;
        switch ($form->get('items/type_of_file/post_value')){
          case 'Settings';
            $content = null;
            break;
          case 'Layout';
          case 'Page';
            $layout = new PluginWfYml(__DIR__.'/new_file/layout.yml');
            $content = wfHelp::getYmlDump($layout->get());
            break;
          default:
            break;
        }
        wfFilesystem::saveFile(wfArray::get($GLOBALS, 'sys/app_dir').'/'.$filename_new, $content);
        $json->set('script', array("PluginWfAjax.update('modal_files_body');", "document.getElementById('modal_file_new_btn_close').click();"));
      }
    }elseif($a=='folder_new'){
      $activetheme = wfArray::get($_SESSION, 'plugin/wf/editor/activetheme');
      $folder = 'theme/'.$activetheme.'/'.wfRequest::get('folder');
      $foldername_new = $folder.'/'.wfRequest::get('foldername_new');
      $form = new PluginWfYml(__DIR__.'/form/folder_new.yml');
      $form->set(null, PluginWfForm::bindAndValidate($form->get()));
      if(!$form->get('is_valid')){
        $json->set('script', array("alert(\"".PluginWfForm::getErrors($form->get(), "\\n")."\");"));
      }elseif(!wfFilesystem::fileExist(wfArray::get($GLOBALS, 'sys/app_dir').'/'.($folder))){
        $json->set('script', array("alert('Folder does not exist!');"));
      }elseif(wfFilesystem::fileExist(wfArray::get($GLOBALS, 'sys/app_dir').'/'.$foldername_new)){
        $json->set('script', array("alert('New folder already exist!');"));
      }else{
        //wfFilesystem::saveFile(wfArray::get($GLOBALS, 'sys/app_dir').'/'.$filename_new, $content);
        wfFilesystem::createDir(wfArray::get($GLOBALS, 'sys/app_dir').'/'.$foldername_new);
        $json->set('script', array("PluginWfAjax.update('modal_files_body');", "document.getElementById('modal_folder_new_btn_close').click();"));
      }
    }
    exit(json_encode($json->get()));
  }
  
  /**
   * Get last part of x/innerHTML/y/innerHTML/33
   * @param type $path
   * @return type
   */
  private function getIdFromPath($path){
    $temp = explode('/', $path);
    return $temp[sizeof($temp)-1];
  }
  private function file_to_id($file){
    $temp = $file;
    $temp = str_replace('/', '.', $temp).'_body';
    return $temp;
  }
  
  /*
   * Function to edit elements...
   */
  function create_elements($arr, $filename, $keys = null){
    $items = array();
    if(is_array($arr)){
      foreach ($arr as $key => $value) {
        $item = new PluginWfArray($value);
        if(!is_null($keys)){
          //$path_to_key = $keys.'/innerHTML/'.$key;
          $path_to_key = $keys.'/innerHTML/'.$key;
        }else{
          $path_to_key = '/innerHTML/'.$key;
        }
        if(substr($path_to_key, 0, 11) == '/innerHTML/'){
          $path_to_key = substr($path_to_key, 11);
        }
        $path_to_key = urlencode($path_to_key);


        $onclick_view = "PluginWfBootstrapjs.modal({id: 'element_view', url: '/editor/elementview?file=".$filename."&key=".$path_to_key."', lable: 'Element', size: 'lg'});return false;";
        $onclick_add = "PluginWfBootstrapjs.modal({id: 'element_add', url: '/editor/elementadd?file=".$filename."&key=".$path_to_key."', lable: 'Add', size: 'sm'});return false;";

        $style_type = 'width:50%;font-weight:bold;cursor: pointer;';
        if($item->get('settings/disabled')){
          $style_type .= 'text-decoration: line-through;';
          $class = 'alert alert-warning';
        }
        if($item->get('type') != 'widget'){
          $innerHTML = null;
          $style = '';
          $class = 'alert alert-success';

          if(is_array($item->get('innerHTML')) || $item->get('innerHTML') === null || $item->get('innerHTML') === ''){
            if(is_array($item->get('innerHTML'))){
              $innerHTML = $this->create_elements($item->get('innerHTML'), $filename, $keys.'/innerHTML/'.$key);
            }
            $btn_add = array('type' => 'a', 'innerHTML' => 'Add', 'attribute' => array('onclick' => $onclick_add, 'style' => 'float:right;margin-right:2px;'));
          }else{
            $btn_add = array('type' => 'span', 'innerHTML' => 'Add', 'attribute' => array('style' => 'float:right;margin-right:2px;'));
            $btn_add = null;
            $innerHTML = htmlentities($item->get('innerHTML')).'';
            $style = 'border:dotted 1px silver';
          }
          $item = array('type' => 'div', 'innerHTML' => array(
              /*array('type' => 'a', 'innerHTML' => 'Edit', 'attribute' => array('onclick' => $onclick_view, 'style' => 'float:right')),*/
              $btn_add,
              array('type' => 'div', 'innerHTML' => ''.$item->get('type').' '.($item->get('attribute/class')?'('.$item->get('attribute/class').')':''), 'attribute' => array('style' => $style_type, 'onclick' => $onclick_view)),
              array('type' => 'div', 'innerHTML' => $innerHTML, 'attribute' => array('style' => $style))
              ), 'attribute' => array('class' => $class));
        }else{
          $item = array('type' => 'div', 'innerHTML' => array(
              array('type' => 'div', 'innerHTML' => 'widget ('.$item->get('data/plugin').')', 'attribute' => array('style' => $style_type, 'onclick' => $onclick_view)),
              array('type' => 'div', 'innerHTML' => 'name: '.$item->get('data/method').'')
              ), 'attribute' => array('class' => 'alert alert-info'));
        }
        $items[] = $item;
      }
    }
    return $items;
  }
  /*
   * Function to get all files and folders...
   */
  function scan_dir($dir){
    $items = array();
    $content = scandir($dir);
    foreach ($content as $key => $value) {
      if($value=='.' || $value=='..'){continue;}
      if(is_file($dir.'/'.$value)){
        $items[] = array('type' => 'file', 'name' => $value, 'dir' => $dir.'/'.$value);
      }else{
        $items[] = array('type' => 'folder', 'name' => $value, 'child' => scan_dir($dir.'/'.$value));
      }
    }
    return $items;
  }
  
  
  
  public function page_files(){
    $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/editor/page/files.yml';
    $page = wfFilesystem::loadYml($filename);
    wfArray::set($GLOBALS, 'sys/layout_path', '/plugin/wf/editor/layout');
    
    //Insert element in page.
    $page = wfArray::set($page, 'content', $this->getFiles());
    
    
    wfDocument::mergeLayout($page);
  }
  
  public function page_file_edit(){
    $this->includePlugin();
    wfPlugin::includeonce('wf/yml');
    $form = new PluginWfYml(__DIR__.'/form/file_edit.yml');
    $form->set('items/filename_old/default', urldecode(wfRequest::get('yml')));
    $form->set('items/filename_new/default', urldecode(wfRequest::get('yml')));
    $element = wfDocument::createWidget('wf/form', 'render', $form->get());
    wfDocument::renderElement(array($element));
  }
  
  public function page_file_new(){
    wfPlugin::includeonce('wf/yml');
    $form = new PluginWfYml(__DIR__.'/form/file_new.yml');
    $form->set('items/folder/default', urldecode(wfRequest::get('yml')));
    $element = wfDocument::createWidget('wf/form', 'render', $form->get());
    wfDocument::renderElement(array($element));
  }
  public function page_folder_new(){
    wfPlugin::includeonce('wf/yml');
    $form = new PluginWfYml(__DIR__.'/form/folder_new.yml');
    $form->set('items/folder/default', urldecode(wfRequest::get('folder')));
    $element = wfDocument::createWidget('wf/form', 'render', $form->get());
    wfDocument::renderElement(array($element));
  }
  
  /**
   Render a page with all installed plugins.
   */
  public function page_plugin(){
    $this->includePlugin();
    
    
    
    
    
    $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/editor/page/plugin.yml';
    $page = wfFilesystem::loadYml($filename);
    
    //Insert element in page.
    $page = wfArray::set($page, 'content', $this->getPlugin());
    
    //wfHelp::yml_dump($page);
    
    //wfHelp::yml_dump($page);
    
    wfArray::set($GLOBALS, 'sys/layout_path', '/plugin/wf/editor/layout');
    wfDocument::mergeLayout($page);
  }
  
  
  function getFiles(){
    
    $activetheme = wfArray::get($_SESSION, 'plugin/wf/editor/activetheme');
    
    
    
//    //Get a panel.
//    $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/editor/data/panel.yml';
//    $panel = wfFilesystem::loadYml($filename);
//    $panel = wfArray::set($panel, 'innerHTML/heading/innerHTML', 'Files');
//    $panel = wfArray::set($panel, 'innerHTML/heading/attribute/onclick', "PluginWfAjax.update('wf_editor_files');");
//    $theme_dir = wfFilesystem::getScandir(wfArray::get($GLOBALS, 'sys/app_dir').'/theme/'.$activetheme);
//    $folders = array();
//    foreach ($theme_dir as $key => $value) {
//      foreach (wfFilesystem::getScandir(wfArray::get($GLOBALS, 'sys/app_dir').'/theme/'.$activetheme.'/'.$value, array('.YML')) as $key2 => $value2) {
//        $folders[$value][] = $value2; // = array('plugin' => $value.'/'.$value2 ); 
//      }
//    }
//    $div = array();
//    $class = wfArray::get($GLOBALS, 'sys/class');
//    foreach ($folders as $key => $value) {
//      $item = array();
//      foreach ($value as $key2 => $value2) {
//        $yml = 'theme/'.$activetheme.'/'.$key.'/'.$value2;
//        $panel_id = str_replace('/', '.', $yml);
//        $yml = urlencode($yml);
//        //$item[] = array('innerHTML' => $value2, 'href' => "javascript:PluginWfBootstrapjs.panel({lable: '$yml', url: '/$class/edit?yml='+encodeURIComponent('$yml'), id: '$panel_id', parent: document.getElementById('wf_editor_workarea')});document.getElementById('modal_files_btn_close').click();");
//        $item[] = array('innerHTML' => $value2, 'href' => "javascript: if(confirm('Edit as text?')){PluginWfBootstrapjs.panel({lable: '$yml', url: '/$class/edit?yml='+encodeURIComponent('$yml'), id: '$panel_id', parent: document.getElementById('wf_editor_workarea')});}else{PluginWfBootstrapjs.panel({lable: '$yml', url: '/$class/element?yml='+encodeURIComponent('$yml'), id: '$panel_id', parent: document.getElementById('wf_editor_workarea')});}document.getElementById('modal_files_btn_close').click();");
//      }
//      $list = wfDocument::createWidget('wf/bootstrap', 'listgroup', array('item' => $item));
//      $icon = wfDocument::createWfElement('widget', array('plugin' => 'davegandy/fontawesome450', 'method' => 'render', 'data' => array('icon' => 'folder-open')));
//      $label = wfDocument::createHtmlElement('label', $key);
//      $div[] = wfDocument::createHtmlElement('div', array($icon, $label, $list));
//    }
//    $panel = wfArray::set($panel, 'innerHTML/content/innerHTML', $div);
    

    $class = wfArray::get($GLOBALS, 'sys/class');

    /**
     * Listgroup.
     */
    $dir = urldecode(wfRequest::get('dir'));
    if($dir){
      $folder = wfFilesystem::getScandir(wfArray::get($GLOBALS, 'sys/app_dir').'/theme/'.$activetheme.'/'.$dir);
    }else{
      $folder = wfFilesystem::getScandir(wfArray::get($GLOBALS, 'sys/app_dir').'/theme/'.$activetheme);
    }
    $a = array();
    if(sizeof($folder) > 0){
      foreach ($folder as $key => $value) {
        $is_file = $this->is_file($dir, $value);
        $glyphicon = null;
        if($dir){
          if($is_file){
            $glyphicon = 'file';
            $yml = ('theme/'.$activetheme.'/'.$dir.'/'. $value);
            $panel_id = str_replace('/', '.', $yml);
            //$onclick = "if(confirm('Edit as text?')){PluginWfBootstrapjs.panel({lable: '$yml', url: '/$class/edit?yml='+encodeURIComponent('$yml'), id: '$panel_id', parent: document.getElementById('wf_editor_workarea')});}else{PluginWfBootstrapjs.panel({lable: '$yml', url: '/$class/element?yml='+encodeURIComponent('$yml'), id: '$panel_id', parent: document.getElementById('wf_editor_workarea')});}document.getElementById('modal_files_btn_close').click();return false;";
            $a[] = $this->getBtnGroup(array('label' => $value, 'list_group_item' => true, 'buttons' => array(
              array('label' => 'Text editor', 'onclick' => "PluginWfBootstrapjs.panel({lable: '$yml', url: '/$class/edit?yml='+encodeURIComponent('$yml'), id: '$panel_id', parent: document.getElementById('wf_editor_workarea')});document.getElementById('modal_files_btn_close').click();return false;"),
              array('label' => 'Element editor', 'onclick' => "PluginWfBootstrapjs.panel({lable: '$yml', url: '/$class/element?yml='+encodeURIComponent('$yml'), id: '$panel_id', parent: document.getElementById('wf_editor_workarea')});document.getElementById('modal_files_btn_close').click();return false;"),
              array('label' => 'Rename/Move/Copy', 'onclick' => "PluginWfBootstrapjs.modal({id: 'modal_file_edit', url: '/editor/file_edit?yml='+encodeURIComponent('".$dir."/". $value."'), lable: 'File', size: 'lg'});return false;"),
              array('label' => 'Delete file', 'onclick' => "if(confirm('Delete file?')){ $.get('/editor/action?a=file_delete&file='+encodeURIComponent('$yml')+'', function(data){PluginWfCallbackjson.call( data );});}return false;"),
              )));
          }else{
            $glyphicon = 'folder-close';
            $onclick = "PluginWfAjax.load('modal_files_body', '/editor/files?dir=".  urlencode($dir.'/'. $value) ."');return false;";
            $a[] = wfDocument::createHtmlElement('a', array(
              wfDocument::createHtmlElement('span', null, array('class' => 'glyphicon glyphicon-'.$glyphicon, 'style' => 'float:right')),
              wfDocument::createHtmlElement('span', $value)
              ), array('onclick' => $onclick, 'class' => 'list-group-item'));
          }
        }else{
          $glyphicon = 'folder-close';
          $onclick = "PluginWfAjax.load('modal_files_body', '/editor/files/dir/". urlencode($value) ."');return false;";
          $a[] = wfDocument::createHtmlElement('a', array(
            wfDocument::createHtmlElement('span', null, array('class' => 'glyphicon glyphicon-'.$glyphicon, 'style' => 'float:right')),
            wfDocument::createHtmlElement('span', $value)
            ), array('onclick' => $onclick, 'class' => 'list-group-item'));
        }
      }
    }else{
      $a[] = wfDocument::createHtmlElement('div', array(wfDocument::createHtmlElement('em', 'Empty folder.')), array('class' => 'well'));
    }
    $list_group = wfDocument::createHtmlElement('div', $a, array('class' => 'list-group'));
    
    /**
     * Breadcrumb.
     */
    $li = array();
    $li[] = wfDocument::createHtmlElement('li', array(wfDocument::createHtmlElement('a', 'root:', array('onclick' => "PluginWfAjax.load('modal_files_body', '/editor/files');return false;"))));
    if($dir){
      $di = explode('/', $dir);
      $str = null;
      //wfHelp::yml_dump(sizeof($di));
      foreach ($di as $key => $value) {
        if($key+1==sizeof($di)){
          $glyphicon = wfDocument::createHtmlElement('span', null, array('class' => 'glyphicon glyphicon-folder-open', 'style' => 'margin-left:10px'));
        }else{
          $glyphicon = null;
        }
        if(!$str){
          $str = $value;
        }else{
          $str .= '/'.$value;
        }
        $li[] = wfDocument::createHtmlElement('li', array(
          wfDocument::createHtmlElement('a', $value, array('onclick' => "PluginWfAjax.load('modal_files_body', '/editor/files?dir=".  urlencode($str)."');return false;")),
          $glyphicon
          ));
      }

    }
    $breadcrumb = wfDocument::createHtmlElement('ol', $li, array('class' => 'breadcrumb'));
    
    
    $btn_group = null;
    if($dir){
      $btn_group = $this->getBtnGroup(array('label' => 'Action', 'btn_class' => 'btn', 'buttons' => array(
        array('label' => 'New file', 'onclick' => "PluginWfBootstrapjs.modal({id: 'modal_file_new', url: '/editor/file_new?yml='+encodeURIComponent('$dir'), lable: 'File new', size: 'sm'});return false;"),
        array('label' => 'New folder', 'onclick' => "PluginWfBootstrapjs.modal({id: 'modal_folder_new', url: '/editor/folder_new?folder='+encodeURIComponent('$dir'), lable: 'Folder new', size: 'sm'});return false;"),
        array('label' => 'Delete folder', 'onclick' => "if(confirm('Delete folder?')){ $.get('/editor/action?a=folder_delete&folder='+encodeURIComponent('$dir')+'', function(data){PluginWfCallbackjson.call( data );});}return false;"),
        array('label' => 'Rename', 'onclick' => 'alert(83);return false;', 'disabled' => true),
        )));
    }
    
    return array($breadcrumb, $btn_group, $list_group);
    
    
  }
  
  function getBtnGroup($data){
    $class = 'btn-group';
    if(isset($data['list_group_item']) && $data['list_group_item']){
      $class .= ' list-group-item';
    }
    //$btn_class = 'btnzzz btn-defaultzzz dropdown-toggle';
    $btn_class = 'dropdown-toggle';
    if(isset($data['btn_class']) && $data['btn_class']){
      $btn_class .= ' '.$data['btn_class'];
    }
    $li = array();
    foreach ($data['buttons'] as $key => $value) {
      $disabled = null;
      if(isset($value['disabled']) && $value['disabled']){
        $disabled = 'disabled';
      }
      $li[] = wfDocument::createHtmlElement('li', array(wfDocument::createHtmlElement('a', $value['label'], array('onclick' => $value['onclick']))), array('class' => $disabled));
    }
    $btn_group = wfDocument::createHtmlElement('div', array(
      wfDocument::createHtmlElement('a', array(
        wfDocument::createHtmlElement('text', $data['label']),
        wfDocument::createHtmlElement('span', null, array('class' => 'caret'))
      ), array('type' => 'button', 'class' => $btn_class, 'data-toggle' => 'dropdown', 'aria-haspopup' => true, 'aria-expanded' => false)),
      wfDocument::createHtmlElement('ul', $li, array('class' => 'dropdown-menu'))
    ), array('class' => $class));
    return $btn_group;
  }
  
  function is_file($dir, $file_or_folder){
    $activetheme = wfArray::get($_SESSION, 'plugin/wf/editor/activetheme');
    $is_file = false;
    if($dir){
      if(is_file(wfArray::get($GLOBALS, 'sys/app_dir').'/theme/'.$activetheme.'/'.$dir.'/'.$file_or_folder)){
        $is_file = true;
      }
    }else{
      if(is_file(wfArray::get($GLOBALS, 'sys/app_dir').'/theme/'.$activetheme.'/'.$file_or_folder)){
        $is_file = true;
      }
    }
    return $is_file;
  }
  
  function getPlugin(){
    //Get a panel.
    $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/editor/data/panel.yml';
    $panel = wfFilesystem::loadYml($filename);
    //Get plugins.
    $org_dir = wfFilesystem::getScandir(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin');
    $plugins = array();
    foreach ($org_dir as $key => $value) {
      if(substr($value, 0, 1)=='.'){
        // We do not want folder begining with ".". 
        continue;
      }
      foreach (wfFilesystem::getScandir(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/'.$value) as $key2 => $value2) {
        $plugins[$value.'/'.$value2] = array('plugin' => $value.'/'.$value2 ); 
      }
    }
    //Create elements of panel.
    $element = array();
    
    $items = array();
    $class = wfArray::get($GLOBALS, 'sys/class');
    foreach ($plugins as $key => $value) {
      $plugin = urlencode($key);
      $onclick = "PluginWfBootstrapjs.modal({id: 'wf_editor_pluginview', url: '/$class/pluginview?plugin=$plugin', lable: 'Plugin', 'size': 'lg'});return false;";
      $item = array('innerHTML' => $key, 'onclick' => $onclick, 'href' => '#');
      
      $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/'.$key.'/config/settings.yml';
      $plugin_settings = null;
      if(wfFilesystem::fileExist($filename)){
        $plugin_settings = wfFilesystem::loadYml($filename);
        if(wfArray::issetAndTrue($plugin_settings, 'deprecated')){
          $item = wfArray::set($item, 'innerHTML', "<i>$key</i>");
        }
      }
      
      
      $items[] = $item;
      
    }
    $element[] = wfDocument::createWidget('wf/bootstrap', 'listgroup', array('item' => $items));
    
    return $element;
    
    $panel = wfArray::set($panel, 'innerHTML/heading/innerHTML', 'Plugin');
    $div = array();
    foreach ($plugins as $key => $value) {
      
      $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/'.$key.'/config/settings.yml';
      $plugin_settings = null;
      if(wfFilesystem::fileExist($filename)){
        $plugin_settings = wfFilesystem::loadYml($filename);
        if(wfArray::issetAndTrue($plugin_settings, 'deprecated')){
          //wfHelp::yml_dump($plugin_settings);
          continue;
        }
      }
      
      $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/'.$key.'/action.class.php';
      $item = array();
      $item[] = wfDocument::createWfElement('widget', array('plugin' => 'davegandy/fontawesome450', 'method' => 'render', 'data' => array('icon' => 'plug')));
      $item[] = wfDocument::createHtmlElement('label', $key, array('data-toggle' => 'collapse', 'data-target' => '#plugin_'.str_replace('/', '_', $key)));
      
      if(wfFilesystem::fileExist($filename)){
        require_once $filename;
        
        
        
        
        
        $class = wfSettings::getPluginObj($key);
        $rc = self::getReflectionClass($class);
        
        $rc_item = array();
        $rc_item[] = wfDocument::createHtmlElement('div', self::cleanComment(wfArray::get($rc,'comment')), array('class' => 'bg-primaryzzz', 'style' => 'font-familyzzz:courier new;border-radius:4px;'));
        foreach (wfArray::get($rc, 'methods') as $key2 => $value2) {
          $class = 'bg-danger';
          $name = $key2;
          if(substr($key2, 0, 7)=='widget_'){
            $class = 'bg-warning';
            $name = substr($name, 7).' (widget)';
          }elseif(substr($key2, 0, 6)=='event_'){
            $class = 'bg-info';
            $name = substr($name, 6).' (event)';
          }elseif(substr($key2, 0, 5)=='page_'){
            $class = 'bg-success';
            $name = substr($name, 5).' (page)';
          }else{
            continue;
          }
          
          $comment = wfArray::get($value2,'comment');
          $code = "\n".$value2['code'];
          $comment .= '<p>Source code.</p>';
          $comment .= '<pre><span style="margin-left:50%;">PHP</span><code class=" language-php">'.$code.'</code></pre>';
          $comment = self::cleanComment($comment, true);
          $comment = self::replace_load_tags($comment);
          
          
          
          $rc_item[] = wfDocument::createHtmlElement('div', "<p>$name</p>".$comment, array('class' => $class, 'style' => 'padding:10px;margin-top:4px;font-familyzzz:courier new;border-radius:4px;'));
        }
        $item[] = wfDocument::createHtmlElement('div', $rc_item, array('id' => 'plugin_'.str_replace('/', '_', $key), 'class' => 'collapse'));
        
      }else{
        $item[] = wfDocument::createHtmlElement('div', '<i>Class file is missing...</i>', array('id' => 'plugin_'.str_replace('/', '_', $key), 'class' => 'collapse'));
      }
      $div[] = wfDocument::createHtmlElement('div', $item, array('style' => 'margin-bottom:20px;border:solid 1px silver;border-radius:4px;padding:5px;'));
    }
    
    $div[] = wfDocument::createHtmlElement('script', 'Prism.highlightAll();');
    
    //return $div;
    
    
    $container = wfDocument::createHtmlElement('div', $div, array('style' => 'padding:4px;'));
    return array($container);
    
//    $panel = wfArray::set($panel, 'innerHTML/content/innerHTML', $div);
//    $element[] = $panel;
//    return $element;
  }
  
  public function page_pluginview(){
    $this->includePlugin();
    $page = wfFilesystem::loadYml(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/editor/page/pluginview.yml');
    $element = array();
    $plugin = urldecode(wfRequest::get('plugin'));
    $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/'.$plugin.'/config/settings.yml';
    $plugin_settings = null;
    if(wfFilesystem::fileExist($filename)){
      $plugin_settings = wfFilesystem::loadYml($filename);
      if(wfArray::issetAndTrue($plugin_settings, 'deprecated')){
        $element[] = wfDocument::createWidget('wf/bootstrap', 'alert', array('rewrite' => array('innerHTML' => 'This plugin is deprecated!', 'attribute/class' => 'alert alert-warning')));
      }
    }
    
    
    
    //$filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/'.$plugin.'/action.class.php';
    wfPlugin::includeonce($plugin);
    
    
    if(true){
      $element[] = wfDocument::createHtmlElement('h2', $plugin);
      $item = array();
      $item[] = array('innerHTML' => 'Methods', 'active' => true);
      $class = wfArray::get($GLOBALS, 'sys/class');
      //if(wfFilesystem::fileExist($filename)){
      if(true){
        //require_once $filename;
        $rc = self::getReflectionClass(wfSettings::getPluginObj($plugin));
        $comment = self::cleanComment(wfArray::get($rc,'comment'));
        $comment = self::replace_load_tags($comment, $plugin);
        $element[] = wfDocument::createHtmlElement('div', $comment, array('class' => 'bg-primaryzzz', 'style' => 'font-familyzzz:courier new;border-radius:4px;'));
        foreach (wfArray::get($rc, 'methods') as $key2 => $value2) {

          $onclick = "PluginWfBootstrapjs.modal({id: 'wf_editor_methodview', url: '/$class/methodview?plugin=".urlencode($plugin)."&method=$key2', lable: 'Method', 'size': 'lg'});return false;";
          $item[] = array('href' => '#', 'innerHTML' => $key2, 'onclick' => $onclick);

        }

      }
      $element[] = wfDocument::createWidget('wf/bootstrap', 'listgroup', array('item' => $item));
    }
    
    
    if(false){
      $element[] = wfDocument::createHtmlElement('h2', $plugin);
      if(wfFilesystem::fileExist($filename)){
        require_once $filename;
        $class = wfSettings::getPluginObj($plugin);
        $rc = self::getReflectionClass($class);
        $comment = self::cleanComment(wfArray::get($rc,'comment'));
        $comment = self::replace_load_tags($comment, $plugin);
        $element[] = wfDocument::createHtmlElement('div', $comment, array('class' => 'bg-primaryzzz', 'style' => 'font-familyzzz:courier new;border-radius:4px;'));
        foreach (wfArray::get($rc, 'methods') as $key2 => $value2) {
          $class = 'bg-danger';
          $name = $key2;
          $type = null;
          if(substr($key2, 0, 7)=='widget_'){
            $class = 'bg-warning';
            $name = substr($name, 7);
            $type = 'widget';
          }elseif(substr($key2, 0, 6)=='event_'){
            $class = 'bg-info';
            $name = substr($name, 6);
            $type = 'event';
          }elseif(substr($key2, 0, 5)=='page_'){
            $class = 'bg-success';
            $name = substr($name, 5);
            $type = 'page';
          }else{
            continue;
          }
          $comment = wfArray::get($value2,'comment');
          $code = "\n".$value2['code'];
          $id = str_replace('/', '_', $plugin.'_'.$key2);
          $comment .= '<a href="#" data-toggle="collapse" data-target="#plugin_source_'.$id.'">Source code.</a>';
          $comment .= '<pre stylezzz="display:none" id="plugin_source_'.$id.'" class="collapse" aria-expanded="false"><span style="margin-left:50%;">PHP</span><code class=" language-php">'.$code.'</code></pre>';
          $comment = self::cleanComment($comment, true);
          $comment = self::replace_load_tags($comment, $plugin);
          $element[] = wfDocument::createHtmlElement('div', "<i>$type</i><h3>$name</h3>".$comment, array('class' => $class, 'style' => 'padding:10px;margin-top:4px;font-familyzzz:courier new;border-radius:4px;'));
          $element[] = wfDocument::createHtmlElement('div', '&nbsp;');
        }
      }else{
        $element[] = wfDocument::createHtmlElement('div', '<i>Class file is missing...</i>');
      }
    }
    
    $element[] = wfDocument::createHtmlElement('script', 'Prism.highlightAll();');
    //$element[] = wfDocument::createHtmlElement('script', "console.log(document.getElementById('btn_widget'));");
    
    $page = wfArray::set($page, 'content', $element);
    wfArray::set($GLOBALS, 'sys/layout_path', '/plugin/wf/editor/layout');
    wfDocument::mergeLayout($page);
  }
  
  
  public static function page_methodview(){
    
    wfPlugin::includeonce('wf/yml');
    
    $method = wfRequest::get('method');
    $page = wfFilesystem::loadYml(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/editor/page/methodview.yml');
    $element = array();
    $plugin = urldecode(wfRequest::get('plugin'));
    //$filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/'.$plugin.'/action.class.php';
    wfPlugin::includeonce($plugin);
    
    $element[] = wfDocument::createHtmlElement('h2', $plugin);
    
    //if(wfFilesystem::fileExist($filename)){
    if(true){
      //require_once $filename;
      $class = wfSettings::getPluginObj($plugin);
      $rc = self::getReflectionClass($class);
      foreach (wfArray::get($rc, 'methods') as $key2 => $value2) {
        if($key2 != $method){
          // Only one method is of interest.
          continue;
        }
        $class = 'bg-danger';
        $name = $key2;
        $type = null;
        if(substr($key2, 0, 7)=='widget_'){
          $class = 'bg-warning';
          $name = substr($name, 7);
          $type = 'widget';
        }elseif(substr($key2, 0, 6)=='event_'){
          $class = 'bg-info';
          $name = substr($name, 6);
          $type = 'event';
        }elseif(substr($key2, 0, 5)=='page_'){
          $class = 'bg-success';
          $name = substr($name, 5);
          $type = 'page';
        }else{
          //continue;
          $class = 'bg-successzzz';
        }
        
        
        $default = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/'.$plugin.'/default/'.$type.'.'.$name.'.yml');
        $default_yml = null;
        if($default->get()){
//          wfHelp::yml_dump(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/'.$plugin.'/default/'.$type.'.'.$name.'.yml');
//          wfHelp::yml_dump($default);
          
          $default_yml = wfDocument::createHtmlElement('pre', array(
          wfDocument::createHtmlElement('span', 'Default values', array('style' => 'margin-left:40%;', 'title' => 'Default values from file: '.'/plugin/'.$plugin.'/default/'.$type.'.'.$name.'.yml')),
            wfDocument::createHtmlElement('code', "\n".trim(wfHelp::getYmlDump($default->get())), array('class' => ' language-yaml'))
            ), array('class' => ' language-yaml'));
        }
        
        
        $comment = wfArray::get($value2,'comment');
        $code = "\n".$value2['code'];
        $id = str_replace('/', '_', $plugin.'_'.$key2);
        $comment .= '<br><a href="#" data-toggle="collapse" data-target="#plugin_source_'.$id.'">Source code.</a>';
        $comment .= '<pre stylezzz="display:none" id="plugin_source_'.$id.'" class="collapse" aria-expanded="false"><span style="margin-left:50%;">PHP</span><code class=" language-php">'.$code.'</code></pre>';
        $comment = self::cleanComment($comment, true);
        $comment = self::replace_load_tags($comment, $plugin);
        //$element[] = wfDocument::createHtmlElement('div', "<a href='#' onclick='document.getElementById(\"btn_widget_add_from_window\").setAttribute(\"data_plugin\", \"$plugin\");document.getElementById(\"btn_widget_add_from_window\").setAttribute(\"data_widget\", \"$name\");document.getElementById(\"btn_widget_add_from_window\").click();return false;' style='float:right;display:none;' class='btn btn-default' id='btn_widget_add'>Add to element</a><i>$type</i><h3>$name</h3>".$comment, array('class' => $class, 'style' => 'padding:10px;margin-top:4px;border-radius:4px;'));
        $element[] = wfDocument::createHtmlElement('div', array(
          wfDocument::createHtmlElement('a', 'Add to element', array(
            'style' => 'float:right;display:none;', 
            'class' => 'btn btn-default',
            'id' => 'btn_widget_add',
            'onclick' => "document.getElementById('btn_widget_add_from_window').setAttribute('data_plugin', '$plugin');document.getElementById('btn_widget_add_from_window').setAttribute('data_widget', '$name');document.getElementById('btn_widget_add_from_window').click();return false;"
          )),
          wfDocument::createHtmlElement('i', $type),
          wfDocument::createHtmlElement('h3', $name),
          $default_yml,
          wfDocument::createHtmlElement('div', $comment)
        ), array('class' => $class, 'style' => 'padding:10px;margin-top:4px;border-radius:4px;'));
        
        
        //<i>$type</i><h3>$name</h3>".$comment
        
        
        
        
        $element[] = wfDocument::createHtmlElement('div', '&nbsp;');
      }
    }
    $element[] = wfDocument::createHtmlElement('script', 'Prism.highlightAll();');
    if($type == 'widget'){
      $element[] = wfDocument::createHtmlElement('script', 'if(document.getElementById("btn_widget")){document.getElementById("btn_widget_add").style.display="";}');
    }
    
    
    $page = wfArray::set($page, 'content', $element);
    wfArray::set($GLOBALS, 'sys/layout_path', '/plugin/wf/editor/layout');
    wfDocument::mergeLayout($page);
  }
  
  
  private static function getReflectionClass($class){
    $return = array();
    $rc = new ReflectionClass($class);
    $return['name'] = $rc->getName();
    $return['comment'] = $rc->getDocComment();
    $return['filename'] = $rc->getFileName();
    $content = file_get_contents($rc->getFileName());
    $methods = $rc->getMethods();
    foreach ($methods as $method) {
      $temp = array();
      $temp['name'] = $method->name;
      $temp['comment'] = $method->getDocComment();
      $temp['code'] = self::getCode($content, $method->getStartLine(), $method->getEndLine());
      $return['methods'][$method->name] = $temp;
    }
    return $return;
  }
  private static function getCode($content, $start, $end){
    $str = null;
    $content = explode("\n", $content);
    foreach ($content as $key => $value) {
      if(($key+1)>= $start && ($key+1)<=$end){
        $str .= $value."\n";
      }
    }
    return $str;
  }
  
  private static function cleanComment($comment, $justify = false){
    
    if($justify){
      //Remove '  ' from left if exist.
      $array = preg_split("/\r\n|\n|\r/", $comment);
      $temp = null;
      foreach ($array as $key => $value) {
        if(substr($value, 0, 2)=='  '){
          $temp .= substr($value, 2, strlen($value))."\n";
        }else{
          $temp .= $value."\n";
        }
      }
      $comment = $temp;
    }
    //$comment = str_replace("<", '&lt;', $comment);
    //$comment = str_replace(">", '&lt;', $comment);
    
    
    $comment = str_replace("#code-php#",        '<pre><span style="margin-left:50%;">PHP</span><code class=" language-php">',                  $comment);
    $comment = str_replace("#code-yml#",        '<pre><span style="margin-left:50%;">YML</span><code class=" language-yaml">',                 $comment);
    $comment = str_replace("#code-javascript#", '<pre><span style="margin-left:50%;">Javascript</span><code class=" language-javascript">',    $comment);
    $comment = str_replace("#code-html#",       '<pre><span style="margin-left:50%;">HTML</span><code class=" language-markup">',              $comment);
    $comment = str_replace("#code#",            '</code></pre>', $comment);
    
    
    
    //$comment = str_replace("\n", '<br>', $comment);
    //$comment = str_replace(" ", '&nbsp;', $comment);
    $comment = str_replace("/*", '', $comment);
    $comment = str_replace("*/", '', $comment);
    $comment = str_replace("*", '', $comment);
    return $comment;
  }
  
  
  private static function replace_load_tags($str, $plugin){
    for($i=0; $i<100; $i++){
      $start = strpos($str, "#load:");
      $end = strpos($str, ":load#");
      if($start && $end){
        $anchor = substr($str, $start, $end-$start+6);
        $src = substr($str, $start+6, $end-$start-6);
        // file_get_contents($src) ...
        
        $src = wfSettings::replaceDir($src);
        $src = str_replace('[plugin]', $plugin, $src);
        $content = null;
        if(wfFilesystem::fileExist($src)){
          $content = file_get_contents($src);
          $content = str_replace('<', '&lt;', $content);
        }else{
          $content = "Could not find file: $src";
        }
        //$content = '...';
        
        $str = str_replace($anchor, $content, $str);
      }else{
        break;
      }
    }
    return $str;
  }
  
  
  
  public function page_theme(){
    
    $this->includePlugin();
    
    $filename = dirname( __FILE__).'/page/theme.yml';
    $page = wfFilesystem::loadYml($filename);
    wfArray::set($GLOBALS, 'sys/layout_path', '/plugin/wf/editor/layout');
    
    //Insert element in page.
    //$page = wfArray::set($page, 'content', $this->getFiles());
    
    $item = array();
    foreach (wfFilesystem::getScandir(wfArray::get($GLOBALS, 'sys/app_dir').'/theme') as $key => $value) {
      $dir = wfFilesystem::getScandir(wfArray::get($GLOBALS, 'sys/app_dir').'/theme/'.$value);
      //wfHelp::yml_dump($dir);
      
      foreach (wfFilesystem::getScandir(wfArray::get($GLOBALS, 'sys/app_dir').'/theme/'.$value) as $key2 => $value2) {
 
        
        $theme = urlencode( $value.'/'.$value2);
        $onclick = "$.get('/editor/themeload?theme=$theme', function(data){PluginWfCallbackjson.call( data );});return false;";
        $item[] = array('href' => '#', 'innerHTML' => $value.'/'.$value2, 'onclick' => $onclick);
      }
      
    }
    
    
    //exit;
    
    
    $data = array();
    $data['item'] = $item;
    $listgroup = wfDocument::createWfElement('widget', array('plugin' => 'wf/bootstrap', 'method' => 'listgroup', 'data' => $data));
    $page = wfArray::set($page, 'content', array($listgroup));
    
    
    wfDocument::mergeLayout($page);
  }
  
  
  public function page_themeload(){
    $theme = urldecode(wfRequest::get('theme'));
    //exit($theme);

    $_SESSION = wfArray::set($_SESSION, 'plugin/wf/editor/activetheme', $theme);
    
    $json = array('reload' => true);
    exit(json_encode($json));
    
    
  }
  
  /**
   * Move array value to another position.
   * @param type $array
   * @param type $key
   * @param type $position, 1 to size of array.
   * @return type
   */
  private function move_to_position($array, $key, $position){
    if(!array_key_exists($key, $array)){
      return null; // The key does not exist.
    }
    $value = $array[$key];
    unset($array[$key]);
    $new = array();
    $p = 1;
    foreach ($array as $k => $v) {
      if($position==$p){
        $new[$key] = $value;
      }
      $new[$k] = $v;
      $p++;
    }
    if($position==$p){
      $new[$key] = $value;
    }
    if(!array_key_exists($key, $new)){
      return null; // Position was not relative to array.
    }
    return $new;
  }

  
  
}
