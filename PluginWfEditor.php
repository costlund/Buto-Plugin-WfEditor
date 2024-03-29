<?php
class PluginWfEditor{
  private $settings = null;
  function __construct($buto = false) {
    if($buto){
      if(!wfUser::hasRole('webmaster')){
        exit('Role webmaster is required!');
      }
      wfPlugin::includeonce('wf/array');
      $this->settings = new PluginWfArray(wfArray::get($GLOBALS, 'sys/settings/plugin_modules/'.wfArray::get($GLOBALS, 'sys/class').'/settings'));
    }
    wfPlugin::enable('form/form_v1');
    wfPlugin::enable('bootstrap/navbar_v1');
    wfPlugin::includeonce('wf/yml');
  }
  public function widget_analyse(){
    $a = new PluginWfArray();
    $a->set('system/app_dir', wfArray::get($GLOBALS, 'sys/app_dir'));
    $a->set('system/web_dir', wfArray::get($GLOBALS, 'sys/web_dir'));
    $a->set('system/sys_dir', wfArray::get($GLOBALS, 'sys/sys_dir'));
    $a->set('theme', wfArray::get($_SESSION, 'plugin/wf/editor/activetheme'));
    $a->set('config/folder_exist', wfFilesystem::fileExist(wfArray::get($GLOBALS, 'sys/app_dir').'/theme/'.wfArray::get($_SESSION, 'plugin/wf/editor/activetheme').'/config'));
    $a->set('config/settings_exist', wfFilesystem::fileExist(wfArray::get($GLOBALS, 'sys/app_dir').'/theme/'.wfArray::get($_SESSION, 'plugin/wf/editor/activetheme').'/config/settings.yml'));
    wfHelp::yml_dump($a);
    $element = array();
    $element[] = wfDocument::createHtmlElement('div', 'analyse...');
    wfDocument::renderElement($element);
  }
  public function page_desktop(){
    if(!wfArray::get($_SESSION, 'plugin/wf/editor/activetheme')){
      $_SESSION = wfArray::set($_SESSION, 'plugin/wf/editor/activetheme', wfArray::get($GLOBALS, 'sys/theme'));
    }
    wfGlobals::setSys('layout_path', '/plugin/wf/editor/layout');
    wfPlugin::includeonce('wf/yml');
    $page = new PluginWfYml('/plugin/wf/editor/page/desktop.yml');
    /**
     * Insert admin layout from theme.
     */
    $page = wfDocument::insertAdminLayout($this->settings, 1, $page);
    $this->includePlugin();    
    wfDocument::mergeLayout($page->get());
  }
  public function page_i18n(){
    wfPlugin::includeonce('wf/yml');
    $user = wfUser::getSession();
    $i18n_folder = wfGlobals::getAppDir().'/theme/'.$user->get('plugin/wf/editor/activetheme').'/i18n';
    $folder_exist = wfFilesystem::fileExist($i18n_folder);
    $result = new PluginWfArray();
    if($folder_exist){
      /**
       * 
       */
      $i18n_files = wfFilesystem::getScandir($i18n_folder);
      /**
       * Clean up.
       */
      foreach($i18n_files as $k => $v){
        if(wfPhpfunc::substr($v, 0, 1)=='_'){
          unset($i18n_files[$k]);
        }
      }
      /*
       * Add data.
       */
      foreach($i18n_files as $v){
        $data = new PluginWfYml($i18n_folder.'/'.$v);
        foreach($data->get() as $k2 => $v2){
          $result->set(wfPhpfunc::str_replace('.yml', '', $v).'_'.$k2, array('la' => wfPhpfunc::str_replace('.yml', '', $v), 'key' => $k2, 'value' => $v2, 'search' => '(translated)'));
        }
      }
      /*
       * Add empty rows.
       */
      foreach($result->get() as $v){
        foreach($i18n_files as $v2){
          $la = wfPhpfunc::str_replace('.yml', '', $v2);
          if(isset($v['key'])){
            if( !wfPhpfunc::strstr($la, '_log') && !$result->get($la."_".$v['key']) ){
              $result->set($la."_".$v['key'], array('la' => $la, 'key' => $v['key'], 'value' => '', 'search' => '(empty)'));
            }
          }
        }
      }
      $temp = array();
      foreach($result->get() as $v){
        $temp[] = $v;
      }
      $result = new PluginWfArray($temp);
      unset($temp);
    }
    wfPlugin::enable('wf/table');
    $element = new PluginWfYml(__DIR__.'/element/'.__FUNCTION__.'.yml');
    $element->setByTag(array('data' => $result->get()));
    wfDocument::renderElement($element->get());
  }
  public function page_i18n_form(){
    wfPlugin::enable('form/form_v1');
    wfPlugin::includeonce('wf/yml');
    $element = new PluginWfYml(__DIR__.'/element/'.__FUNCTION__.'.yml');
    $element->setByTag(array('method' => 'render'));
    wfDocument::renderElement($element->get());
  }
  public function page_i18n_capture(){
    wfPlugin::enable('form/form_v1');
    wfPlugin::includeonce('wf/yml');
    $element = new PluginWfYml(__DIR__.'/element/page_i18n_form.yml');
    $element->setByTag(array('method' => 'capture'));
    wfDocument::renderElement($element->get());
  }
  public function form_i18n_capture(){
    $user = wfUser::getSession();
    wfPlugin::includeonce('string/array');
    $sa = new PluginStringArray();
    $excel_data = $sa->from_excel_data(wfRequest::get('excel_data'));
    if($excel_data['columns']!=3){
      return array("alert('There should be 3 columns and not ".$excel_data['columns'].".')");
    }
    $id = $user->get('plugin/wf/editor/activetheme');
    foreach($excel_data['data'] as $v){
      $la = $v[0];
      $key = $v[1];
      $value = $v[2];
      $i18n_yml = new PluginWfYml(wfGlobals::getAppDir().'/theme/'.$id.'/i18n/'.$la.'.yml');
      $i18n_yml->set($key, $value);
      $i18n_yml->sort();
      $i18n_yml->save();
    }
    return array("$('#modal_i18n_form').modal('hide')");
  }
  /**
   * Edit page.
   */
  public function page_edit(){
    $this->includePlugin();
    wfGlobals::setSys('layout_path', '/plugin/wf/editor/layout');
    $yml = wfRequest::get('yml');
    if(wfRequest::isPost()){
      $yml_content = wfRequest::get('yml_content');
      try {
        $array = sfYaml::load($yml_content);
        wfFilesystem::saveFile(wfArray::get($GLOBALS, 'sys/app_dir').'/'.$yml, $yml_content);
        $json = array('success' => true, 'alert' => array('Saved.'), 'removezzz' => array(wfPhpfunc::str_replace('/', '.', $yml)));
        exit(json_encode($json));
      } catch (Exception $exc) {
        $json = array('success' => false, 'alert' => array('An error occure.'));
        exit(json_encode($json));
      }
    }else{
      $yml_decode = urldecode($yml);
      $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/'.$yml_decode;
      $yml_content = file_get_contents($filename);
      $close_onclick = 'PluginWfDom.remove(\''.str_replace('/', '.', $yml_decode).'\');return false;';
      $textarea_script_onkeypress = "document.getElementById('yml_content').onkeypress = function(event){if(event.ctrlKey && event.which==115){console.log(event.ctrlKey+':'+event.which);document.getElementById('".str_replace('/', '.', $yml_decode).'_save'."').onclick();return false;}}";
      $page2 = new PluginWfYml(__DIR__.'/page/edit.yml');
      $page2->setByTag(array(
        'id' => wfPhpfunc::str_replace('/', '.', $yml_decode).'_save', 
        'close_onclick' => $close_onclick,
        'lable' => $yml_decode,
        'yml_decode' => $yml_decode,
        'textarea_script_onkeypress' => $textarea_script_onkeypress,
        'yml_content' => $yml_content
      ));
      wfDocument::mergeLayout($page2->get());
    }
    return null;
  }
  /**
   * Element page.
   */
  public function page_element(){
    $this->includePlugin();
    wfGlobals::setSys('layout_path', '/plugin/wf/editor/layout');
    $yml = wfRequest::get('yml');
    $yml = urldecode($yml);
    if(wfRequest::isPost()){
    }else{
      $onclick_add = "PluginWfBootstrapjs.modal({id: 'element_add', url: '/editor/elementadd?file=".urlencode($yml)."&key=', lable: 'Add', size: 'sm'});return false;";
      wfDocument::renderElement(array(
        wfDocument::createHtmlElement('a', 'Reload', array('class' => 'btn', 'onclick' => "PluginWfAjax.update('".str_replace('/', '.', $yml)."_body');return false;")),
        wfDocument::createHtmlElement('a', 'Add', array('class' => 'btn', 'onclick' => $onclick_add)),
        wfDocument::createHtmlElement('a', 'Collapse', array('class' => 'btn', 'onclick' => "PluginWfEmbed.expand();"))
        ));
      wfPlugin::includeonce('wf/yml');
      wfPlugin::includeonce('wf/array');
      $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.$yml);
      $element = $this->create_elements($yml->get('content'), wfRequest::get('yml'));
      wfDocument::renderElement(($element));
    }
    return null;
  }
  /**
   * Method to get yml.
   */
  private function getYml($array){
    $temp = wfHelp::getYmlDump($array);
    if($temp=='null'){
      return '';
    }else{
      return $temp;
    }
  }
  /**
   * Element view page.
   */
  public function page_elementview(){
    $key = urldecode(wfRequest::get('key'));
    $filename = urldecode(wfRequest::get('file'));
    wfPlugin::includeonce('wf/yml');
    $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.$filename, 'content/'.$key);
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
    $onclick = "PluginWfBootstrapjs.modal({id: 'element_html', url: '/editor/elementkey?file=".urlencode($filename)."&key=".urlencode($key)."', lable: 'Key', size: 'lg'});return false;";
    $widget->set('view/innerHTML/key/innerHTML', "Key: $key");
    $widget->setById('btn_delete', 'attribute/data_file', urldecode(wfRequest::get('file')));
    $widget->setById('btn_delete', 'attribute/data_key', $key);
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
  /**
   * Element attribute page.
   */
  public function page_elementattribute(){
    $this->includePlugin();
    wfPlugin::includeonce('wf/yml');
    $form = new PluginWfYml(__DIR__.'/form/attribute.yml');
    $form->set('items/attribute_origin/default', wfRequest::get('attribute'));
    $form->set('items/attribute/default', wfRequest::get('attribute'));
    $form->set('items/file/default', urldecode(wfRequest::get('file')));
    $form->set('items/key/default', urldecode(wfRequest::get('key')));
    $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.urldecode(wfRequest::get('file')), 'content/'.urldecode(wfRequest::get('key').'/attribute/'.urldecode(wfRequest::get('attribute'))));
    $form->set('items/value/default', $yml->get());
    $element = wfDocument::createWidget('form/form_v1', 'render', $form->get());
    wfDocument::renderElement(array($element));
    return null;
  }
  /**
   * Element html page.
   */
  public function page_elementhtml(){
    $this->includePlugin();
    wfPlugin::includeonce('wf/yml');
    $form = new PluginWfYml(__DIR__.'/form/html.yml');
    $form->set('items/file/default', urldecode(wfRequest::get('file')));
    $form->set('items/key/default', urldecode(wfRequest::get('key')));
    $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.urldecode(wfRequest::get('file')), 'content/'.urldecode(wfRequest::get('key')).'/innerHTML');
    $form->set('items/html/default', $yml->get());
    $element = wfDocument::createWidget('form/form_v1', 'render', $form->get());
    wfDocument::renderElement(array($element));
    return null;
  }
  /**
   * Method to switch theme.
   */
  private function includePlugin(){
    $GLOBALS['sys']['settings']['plugin']['wf']['editor']['enabled'] = 'true';
    $GLOBALS['sys']['settings']['plugin']['wf']['form']['enabled'] = 'true';
    $GLOBALS['sys']['settings']['plugin']['wf']['form_v2']['enabled'] = 'true';
    $GLOBALS['sys']['settings']['plugin']['wf']['bootstrap']['enabled'] = 'true';
    $GLOBALS['sys']['settings']['plugin']['wf']['embed']['enabled'] = 'true';
    $GLOBALS['sys']['settings']['plugin']['datatable']['datatable_1_10_18']['enabled'] = 'true';
  }
  /**
   * Element settings page.
   */
  public function page_elementsettings(){
    $this->includePlugin();
    wfPlugin::includeonce('wf/yml');
    $form = new PluginWfYml(__DIR__.'/form/settings.yml');
    $form->set('items/file/default', urldecode(wfRequest::get('file')));
    $form->set('items/key/default', urldecode(wfRequest::get('key')));
    $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.urldecode(wfRequest::get('file')), 'content/'.urldecode(wfRequest::get('key')).'/settings');
    $form->set('items/settings/default', wfHelp::getYmlDump($yml->get()));
    $element = wfDocument::createWidget('form/form_v1', 'render', $form->get());
    $script = wfDocument::createHtmlElement('script', "PluginWfTextareatab.setTextareaTabEnabled(document.getElementById('frm_element_settings_settings'), '  ');");
    wfDocument::renderElement(array($element, $script));
    return null;
  }
  /**
   * Element data page.
   */
  public function page_elementdata(){
    $this->includePlugin();
    wfPlugin::includeonce('wf/yml');
    $form = new PluginWfYml(__DIR__.'/form/data.yml');
    $form->set('items/file/default', urldecode(wfRequest::get('file')));
    $form->set('items/key/default', urldecode(wfRequest::get('key')));
    $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.urldecode(wfRequest::get('file')), 'content/'.urldecode(wfRequest::get('key')).'/data/data');
    $form->set('items/data/default', wfHelp::getYmlDump($yml->get()));
    $element = wfDocument::createWidget('form/form_v1', 'render', $form->get());
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
  /**
   * Element add page.
   */
  public function page_elementadd(){
    $this->includePlugin();
    wfPlugin::includeonce('wf/yml');
    $element = new PluginWfYml(__DIR__.'/page/elementadd.yml');
    $onclick = "PluginWfBootstrapjs.modal({id: 'element_add_html', url: '/editor/elementaddhtml?file=".(wfRequest::get('file'))."&key=".(wfRequest::get('key'))."', lable: 'Add HTML element', size: 'sm'});";
    $onclick .= "return false;";
    $element->set('group/innerHTML/btn_html_element/attribute/onclick', $onclick);
    $onclick = "PluginWfBootstrapjs.modal({id: 'element_add_html_object', url: '/editor/elementaddhtmlobject?file=".(wfRequest::get('file'))."&key=".(wfRequest::get('key'))."', lable: 'Add HTML element', size: 'sm'});";
    $onclick .= "return false;";
    $element->set('group/innerHTML/btn_html_object_element/attribute/onclick', $onclick);
    $onclick = "PluginWfBootstrapjs.modal({id: 'modal_plugin', url: '/editor/plugin', lable: 'Plugin', size: 'lg'});";
    $onclick .= "return false;";
    $element->set('group/innerHTML/btn_widget/attribute/onclick', $onclick);
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
      if($this->handle_move_param('copy')){
        $element->setById('info_move_h4', 'innerHTML', 'Copy element');
        $element->setById('info_move_btn', 'innerHTML', 'Copy');
      }
    }
    wfDocument::renderElement(($element->get()));
  }
  /**
   * Element add html page.
   */
  public function page_elementaddhtml(){
    $this->includePlugin();
    wfPlugin::includeonce('wf/yml');
    $form = new PluginWfYml(__DIR__.'/form/addhtml.yml');
    $form->set('items/file/default', urldecode(wfRequest::get('file')));
    $form->set('items/key/default', urldecode(wfRequest::get('key')));
    $element = wfDocument::createWidget('form/form_v1', 'render', $form->get());
    wfDocument::renderElement(array($element));
  }
  /**
   * Element add html object page.
   */
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
    $element = wfDocument::createWidget('form/form_v1', 'render', $form->get());
    wfDocument::renderElement(array($element));
  }
  /**
   * Element add widget page.
   */
  public function page_elementaddwidget(){
    wfPlugin::includeonce('wf/yml');
    $form = new PluginWfYml(__DIR__.'/form/addwidget.yml');
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
    $element = wfDocument::createWidget('form/form_v1', 'render', $form->get());
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
  /**
   * Method to delete an element.
   */
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
  /**
   * Action page.
   */
  public function page_action(){
    wfPlugin::includeonce('wf/yml');
    wfPlugin::includeonce('wf/array');
    wfPlugin::includeonce('form/form_v1');
    $a = wfRequest::get('a');
    $json = new PluginWfArray();
    $json->set('success', false);
    if($a=='delete'){
      $this->delete_element(urldecode(wfRequest::get('file')), urldecode(wfRequest::get('key')));
      $json->set('script', array("PluginWfAjax.update('".$this->file_to_id(urldecode(wfRequest::get('file')))."');", "$('.modal').modal('hide');"));
    }elseif($a=='move_set'){
      $this->handle_move_param('set');
      $json->set('script', array("$('.modal').modal('hide');"));
    }elseif($a=='copy_set'){
      $this->handle_move_param('set', true);
      $json->set('script', array("$('.modal').modal('hide');"));
    }elseif($a=='move_do' || $a=='copy_do'){
      /**
       * Move element.
       */
      if($this->handle_move_param('get')){
        $value = $this->handle_move_param('get');
        $move = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.$value['file'], 'content/'.$value['key']);
        if(wfPhpfunc::strlen(wfRequest::get('key'))){
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
        $json->set('script', array("PluginWfAjax.update('".$this->file_to_id(urldecode(wfRequest::get('file')))."');", "$('.modal').modal('hide');"));
      }
    }elseif($a=='move_discard'){
      $this->handle_move_param('unset');
      $json->set('script', array("if(typeof PluginWfAjax == 'object'){PluginWfAjax.update('element_add_body');}"));
    }elseif($a=='attributesave'){
      $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.urldecode(wfRequest::get('file')), 'content/'.urldecode(wfRequest::get('key').'/attribute/'.urldecode(wfRequest::get('attribute'))));
      $form = new PluginWfYml(__DIR__.'/form/attribute.yml');
      $form_form_v1 = new PluginFormForm_v1();
      $form_form_v1->setData($form->get());
      $form_form_v1->bindAndValidate();
      $form->set(null, $form_form_v1->data);
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
        $json->set('script', array("PluginWfAjax.update('element_view_body');", "$('#element_attribute').modal('hide');"));
      }else{
        $json->set('script', array("alert(\"".$form_form_v1->getErrors("\\n")."\");"));
      }
    }elseif($a=='attribute_delete'){
      $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.urldecode(wfRequest::get('file')), 'content/'.urldecode(wfRequest::get('key')));
      $yml->setUnset('attribute/'.urldecode(wfRequest::get('attribute')));
      $yml->save();
      $json->set('script', array("PluginWfAjax.update('".$this->file_to_id(urldecode(wfRequest::get('file')))."');", "PluginWfAjax.update('element_view_body');", "$('#element_attribute').modal('hide');"));
    }elseif($a=='htmlsave'){
      $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.urldecode(wfRequest::get('file')), 'content/'.urldecode(wfRequest::get('key').'/innerHTML'));
      $form = new PluginWfYml(__DIR__.'/form/html.yml');
      $form_form_v1 = new PluginFormForm_v1();
      $form_form_v1->setData($form->get());
      $form_form_v1->bindAndValidate();
      $form->set(null, $form_form_v1->data);
      if($form->get('is_valid')){
        $yml->set(null, $form->get('items/html/post_value'));
        $yml->save();
        $script = array();
        $script[] = "PluginWfAjax.update('element_view_body');";
        if(!$form->get('items/stay/post_value')){
          $script[] = "$('#element_html').modal('hide');";
        }
        $json->set('script', $script);
      }else{
        $json->set('script', array("alert(\"".$form_form_v1->getErrors("\\n")."\");"));
      }
    }elseif($a=='settingssave'){
      $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.urldecode(wfRequest::get('file')), 'content/'.urldecode(wfRequest::get('key').'/settings'));
      $form = new PluginWfYml(__DIR__.'/form/settings.yml');
      $form_form_v1 = new PluginFormForm_v1();
      $form_form_v1->setData($form->get());
      $form_form_v1->bindAndValidate();
      $form->set(null, $form_form_v1->data);
      if($form->get('is_valid')){
        $value = $form->get('items/settings/post_value');
        try {
          $value = sfYaml::load($value);
          $yml->set(null, $value);
          $yml->save();
          $script = array();
          $script[] = "PluginWfAjax.update('element_view_body');";
          if(!$form->get('items/stay/post_value')){
            $script[] = "$('#element_settings').modal('hide');";
          }
          $json->set('script', $script);
        } catch (Exception $exc) {
          $json->set('script', array("alert(\"Unable to parse yml!\");"));
        }
      }else{
        $json->set('script', array("alert(\"".$form_form_v1->getErrors("\\n")."\");"));
      }
    }elseif($a=='datasave'){
      $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.urldecode(wfRequest::get('file')), 'content/'.urldecode(wfRequest::get('key').'/data/data'));
      $form = new PluginWfYml(__DIR__.'/form/data.yml');
      $form_form_v1 = new PluginFormForm_v1();
      $form_form_v1->setData($form->get());
      $form_form_v1->bindAndValidate();
      $form->set(null, $form_form_v1->data);
      if($form->get('is_valid')){
        $value = $form->get('items/data/post_value');
        try {
          $value = sfYaml::load($value);
          $yml->set(null, $value);
          $yml->save();
          $script = array();
          $script[] = "PluginWfAjax.update('element_view_body');";
          if(!$form->get('items/stay/post_value')){
            $script[] = "$('#element_data').modal('hide');";
          }
          $json->set('script', $script);
        } catch (Exception $exc) {
          $json->set('script', array("alert(\"Unable to parse yml!\");"));
        }
      }else{
        $json->set('script', array("alert(\"".$form_form_v1->getErrors("\\n")."\");"));
      }
    }elseif($a=='addhtmlsave'){
      if(wfRequest::get('key')){
        $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.(wfRequest::get('file')), 'content/'.wfRequest::get('key').'/innerHTML');
      }else{
        $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.(wfRequest::get('file')), 'content');
      }
      $form = new PluginWfYml(__DIR__.'/form/addhtml.yml');
      $form_form_v1 = new PluginFormForm_v1();
      $form_form_v1->setData($form->get());
      $form_form_v1->bindAndValidate();
      $form->set(null, $form_form_v1->data);
      if($form->get('is_valid')){
        $inner_html = null;
        /**
         * Inner HTML.
         */
        if($form->get('items/inner_html/post_value')){
          $inner_html = $form->get('items/inner_html/post_value');
        }
        /**
         * Attribute.
         */
        $attribute = array();
        if($form->get('items/class/post_value')){
          $attribute['class'] = $form->get('items/class/post_value');
        }
        for($i=1; $i<=3; $i++){
          if($form->get("items/attribute_".$i."_key/post_value")){
            $attribute[$form->get("items/attribute_".$i."_key/post_value")] = $form->get("items/attribute_".$i."_value/post_value");
          }
        }
        /**
         * Create element.
         */
        $element = wfDocument::createHtmlElement($form->get('items/html_tag/post_value'), $inner_html, $attribute);
        /**
         * Handle yml key.
         */
        if($form->get('items/id/post_value')){
          $yml->set($form->get('items/id/post_value'), $element);
        }else{
          $id = wfCrypt::getUid();
          $yml->set($id, $element);
        }
        $yml->save();
        $json->set('script', array("PluginWfAjax.update('".$this->file_to_id(urldecode(wfRequest::get('file')))."');", "$('.modal').modal('hide');"));
      }else{
        $json->set('script', array("alert(\"".$form_form_v1->getErrors("\\n")."\");"));
      }
    }elseif($a=='addwidget'){
      if(wfRequest::get('key')){
        $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.urldecode(wfRequest::get('file')), 'content/'.urldecode(wfRequest::get('key')).'');
      }else{
        $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.urldecode(wfRequest::get('file')));
      }
      $data = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/'.urldecode(wfRequest::get('plugin')).'/default/widget.'.urldecode(wfRequest::get('widget')).'.yml');
      if($data->get()){
        $widget = wfDocument::createWidget(urldecode(wfRequest::get('plugin')), urldecode(wfRequest::get('widget')), $data->get());
      }else{
        $widget = wfDocument::createWidget(urldecode(wfRequest::get('plugin')), urldecode(wfRequest::get('widget')));
      }
      if(wfRequest::get('key')){
        $yml->set('innerHTML/', $widget);
      }else{
        $yml->set('content/', $widget);
      }
      $yml->save();
      $json->set('script', array("PluginWfAjax.update('".$this->file_to_id(urldecode(wfRequest::get('file')))."');", "$('.modal').modal('hide');"));
    }elseif($a=='addhtmlobjectsave'){
      if(wfRequest::get('key')){
        $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.(wfRequest::get('file')), 'content/'.wfRequest::get('key').'');
      }else{
        $yml = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/'.(wfRequest::get('file')));
      }
      $form = new PluginWfYml(__DIR__.'/form/addhtmlobject.yml');
      $form_form_v1 = new PluginFormForm_v1();
      $form_form_v1->setData($form->get());
      $form_form_v1->bindAndValidate();
      $form->set(null, $form_form_v1->data);
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
          if($form->get('items/id/post_value')){
            if(wfRequest::get('key')){
              $yml->set('innerHTML/'.$form->get('items/id/post_value'), $object->get('content/0'));
            }else{
              $yml->set('content/'.$form->get('items/id/post_value'), $object->get('content/0'));
            }
          }else{
            if(wfRequest::get('key')){
              $yml->set('innerHTML/', $object->get('content/0'));
            }else{
              $yml->set('content/', $object->get('content/0'));
            }
          }
        }
        $yml->save();
        $json->set('script', array("PluginWfAjax.update('".$this->file_to_id(urldecode(wfRequest::get('file')))."');", "$('#element_add_html_object').modal('hide');"));
      }else{
        $json->set('script', array("alert(\"".$form_form_v1->getErrors("\\n")."\");"));
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
            $yml_parent->save();
          }
          $json->set('script', array("PluginWfAjax.update('".$this->file_to_id(urldecode(wfRequest::get('file')))."');"));
        }
      }
    }elseif($a=='file_edit'){
      $filename_old = wfRequest::get('filename_old');
      $filename_new = wfRequest::get('filename_new');
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
              $json->set('script', array("PluginWfAjax.update('modal_files_body');", "$('#modal_file_edit').modal('hide');"));
            }else{
            $json->set('script', array("alert('Could not rename file!');"));
            }
          }else{
            if(copy(wfArray::get($GLOBALS, 'sys/app_dir').'/'.$filename_old, wfArray::get($GLOBALS, 'sys/app_dir').'/'.$filename_new)){
              $json->set('script', array("PluginWfAjax.update('modal_files_body');", "$('#modal_file_edit').modal('hide');"));
            }else{
              $json->set('script', array("alert('Could not copy file!');"));
            }
          }
        }
      }else{
        $json->set('script', array("$('#modal_file_edit').modal('hide');"));
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
      $form_form_v1 = new PluginFormForm_v1();
      $form_form_v1->setData($form->get());
      $form_form_v1->bindAndValidate();
      $form->set(null, $form_form_v1->data);
      if(!$form->get('is_valid')){
        $json->set('script', array("alert(\"".$form_form_v1->getErrors("\\n")."\");"));
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
        $json->set('script', array("PluginWfAjax.update('modal_files_body');", "$('#modal_file_new').modal('hide');"));
      }
    }elseif($a=='folder_new'){
      $activetheme = wfArray::get($_SESSION, 'plugin/wf/editor/activetheme');
      $folder = 'theme/'.$activetheme.'/'.wfRequest::get('folder');
      $foldername_new = $folder.'/'.wfRequest::get('foldername_new');
      $form = new PluginWfYml(__DIR__.'/form/folder_new.yml');
      $form_form_v1 = new PluginFormForm_v1();
      $form_form_v1->setData($form->get());
      $form_form_v1->bindAndValidate();
      $form->set(null, $form_form_v1->data);
      if(!$form->get('is_valid')){
        $json->set('script', array("alert(\"".$form_form_v1->getErrors("\\n")."\");"));
      }elseif(!wfFilesystem::fileExist(wfArray::get($GLOBALS, 'sys/app_dir').'/'.($folder))){
        $json->set('script', array("alert('Folder does not exist!');"));
      }elseif(wfFilesystem::fileExist(wfArray::get($GLOBALS, 'sys/app_dir').'/'.$foldername_new)){
        $json->set('script', array("alert('New folder already exist!');"));
      }else{
        wfFilesystem::createDir(wfArray::get($GLOBALS, 'sys/app_dir').'/'.$foldername_new);
        $json->set('script', array("PluginWfAjax.update('modal_files_body');", "$('#modal_folder_new').modal('hide');"));
      }
    }
    exit(json_encode($json->get()));
  }
  /**
   * Get last part of x/innerHTML/y/innerHTML/33
   * @param string $path
   * @return string
   */
  private function getIdFromPath($path){
    $temp = explode('/', $path);
    return $temp[sizeof($temp)-1];
  }
  /**
   * Method to modify string.
   */
  private function file_to_id($file){
    $temp = $file;
    $temp = wfPhpfunc::str_replace('/', '.', $temp).'_body';
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
          $path_to_key = $keys.'/innerHTML/'.$key;
        }else{
          $path_to_key = '/innerHTML/'.$key;
        }
        if(wfPhpfunc::substr($path_to_key, 0, 11) == '/innerHTML/'){
          $path_to_key = wfPhpfunc::substr($path_to_key, 11);
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
          $uid = wfCrypt::getUid();
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
          
          $pre_attribute = null;
          if($item->get('attribute')){
            $temp = $item->get('attribute');
            ksort($temp);
            $pre_attribute = wfDocument::createHtmlElement('pre', wfHelp::getYmlDump(($temp)), array('style' => 'background:none;border:none;padding:0px'));
          }
          
          $item = array('type' => 'div', 'innerHTML' => array(
              $btn_add,
              array('type' => 'span', 'innerHTML' => ''.$item->get('type'), 'attribute' => array('style' => $style_type, 'onclick' => $onclick_view, 'id' => 'header_'.$uid)),
              wfDocument::createHtmlElement('a', '&nbsp;&nbsp;', array('style' => 'background:white;', 'data-bs-toggle' => 'collapse', 'href' => '#innerHTML_'.$uid, 'class' => 'btn_child caret')),
              $pre_attribute,
              array('type' => 'div', 'innerHTML' => $innerHTML, 'attribute' => array('style' => $style, 'id' => 'innerHTML_'.$uid, 'class' => 'collapse'))
              ), 'attribute' => array('class' => $class, 'id' => 'element_'.$uid));
        }else{
          $active_theme_settings = new PluginWfArray(wfSettings::getSettings('/theme/'.wfArray::get($_SESSION, 'plugin/wf/editor/activetheme').'/config/settings.yml'));
          $alert = null;
          if(!$active_theme_settings->get('plugin/'.$item->get('data/plugin').'/enabled')){
            $alert = wfDocument::createHtmlElement('div', 'Plugin not enabled in theme config/settings.yml!', array('class' => 'alert alert-danger'));
          }
          $item = array('type' => 'div', 'innerHTML' => array(
              array('type' => 'div', 'innerHTML' => 'widget ('.$item->get('data/plugin').')', 'attribute' => array('style' => $style_type, 'onclick' => $onclick_view)),
              array('type' => 'div', 'innerHTML' => 'name: '.$item->get('data/method').''), $alert
              ), 'attribute' => array('class' => 'alert alert-info'));
        }
        $items[] = $item;
      }
    }
    return $items;
  }
  /**
   * Page to list files.
   */        
  public function page_files(){
    $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/editor/page/files.yml';
    $page = wfFilesystem::loadYml($filename);
    wfGlobals::setSys('layout_path', '/plugin/wf/editor/layout');
    $page = wfArray::set($page, 'content', $this->getFiles());
    wfDocument::mergeLayout($page);
  }
  /**
   * Page to edit file.
   */
  public function page_file_edit(){
    $this->includePlugin();
    wfPlugin::includeonce('wf/yml');
    $form = new PluginWfYml(__DIR__.'/form/file_edit.yml');
    $form->set('items/filename_old/default', urldecode(wfRequest::get('yml')));
    $form->set('items/filename_new/default', urldecode(wfRequest::get('yml')));
    $element = wfDocument::createWidget('form/form_v1', 'render', $form->get());
    wfDocument::renderElement(array($element));
  }
  /**
   * Page to add file.
   */
  public function page_file_new(){
    $this->includePlugin();
    wfPlugin::includeonce('wf/yml');
    $form = new PluginWfYml(__DIR__.'/form/file_new.yml');
    $form->set('items/folder/default', urldecode(wfRequest::get('yml')));
    $element = wfDocument::createWidget('form/form_v1', 'render', $form->get());
    wfDocument::renderElement(array($element));
  }
  /**
   * Page to add new folder.
   */
  public function page_folder_new(){
    $this->includePlugin();
    wfPlugin::includeonce('wf/yml');
    $form = new PluginWfYml(__DIR__.'/form/folder_new.yml');
    $form->set('items/folder/default', urldecode(wfRequest::get('folder')));
    $element = wfDocument::createWidget('form/form_v1', 'render', $form->get());
    wfDocument::renderElement(array($element));
  }
  /**
   Render a page with all installed plugins.
   */
  public function page_plugin(){
    $this->includePlugin();
    $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/editor/page/plugin.yml';
    $page = wfFilesystem::loadYml($filename);
    $page = wfArray::set($page, 'content', $this->getPlugin());
    wfGlobals::setSys('layout_path', '/plugin/wf/editor/layout');
    wfDocument::mergeLayout($page);
  }
  /**
   * Method to get files.
   */
  function getFiles(){
    $activetheme = wfArray::get($_SESSION, 'plugin/wf/editor/activetheme');
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
        /**
         * Filter to exclude all files and folder who start with ".".
         */
        if(wfPhpfunc::substr($value, 0, 1) == '.'){ continue; }
        /**
         * 
         */
        $is_file = $this->is_file($dir, $value);
        $glyphicon = null;
        if($dir){
          if($is_file){
            $glyphicon = 'file';
            $yml = ('theme/'.$activetheme.'/'.$dir.'/'. $value);
            $panel_id = wfPhpfunc::str_replace('/', '.', $yml);
            $a[] = $this->getBtnGroup(array('label' => $value, 'list_group_item' => true, 'buttons' => array(
              array('label' => 'Text editor', 'onclick' => "PluginWfBootstrapjs.panel({lable: '$yml', url: '/$class/edit?yml='+encodeURIComponent('$yml'), id: '$panel_id', parent: document.getElementById('wf_editor_workarea')});$('.modal').modal('hide');return false;"),
              array('label' => 'Element editor', 'onclick' => "PluginWfBootstrapjs.panel({lable: '$yml', url: '/$class/element?yml='+encodeURIComponent('$yml'), id: '$panel_id', parent: document.getElementById('wf_editor_workarea')});$('.modal').modal('hide');return false;"),
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
  /**
   * Method to get group of buttons.
   */
  function getBtnGroup($data){
    $class = 'btn-group';
    if(isset($data['list_group_item']) && $data['list_group_item']){
      $class .= ' list-group-item';
    }
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
      ), array('type' => 'button', 'class' => $btn_class, 'data-bs-toggle' => 'dropdown', 'aria-haspopup' => true, 'aria-expanded' => false)),
      wfDocument::createHtmlElement('ul', $li, array('class' => 'dropdown-menu'))
    ), array('class' => $class));
    return $btn_group;
  }
  /**
   * Method to check if is file.
   */
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
  /**
   * Metod to create elements for listing all plugins.
   */
  function getPlugin(){
    $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/editor/data/panel.yml';
    $org_dir = wfFilesystem::getScandir(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin');
    /**
     * Grab plugins in array.
     */
    $plugins = array();
    foreach ($org_dir as $key => $value) {
      if(wfPhpfunc::substr($value, 0, 1)=='.'){
        /**
         * We do not want folder begining with ".". 
         */
        continue;
      }
      foreach (wfFilesystem::getScandir(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/'.$value) as $key2 => $value2) {
        $plugins[$value.'/'.$value2] = array('plugin' => $value.'/'.$value2 ); 
      }
    }
    /**
     * Create table.
     */
    $element = array();
    $table = wfDocument::createHtmlElementAsObject('table', null, array('id' => 'table_plugin', 'class' => 'table table-hover'));
    $tr = array();
    $td = array();
    $td[] = wfDocument::createHtmlElement('th', 'Name');
    $td[] = wfDocument::createHtmlElement('th', 'Deprecated');
    $tr[] = wfDocument::createHtmlElement('tr', $td);
    $thead = wfDocument::createHtmlElement('thead', $tr);
    $tr = array();
    $class = wfArray::get($GLOBALS, 'sys/class');
    foreach ($plugins as $key => $value) {
      $plugin = urlencode($key);
      $onclick = "PluginWfBootstrapjs.modal({id: 'wf_editor_pluginview', url: '/$class/pluginview?plugin=$plugin', lable: 'Plugin', 'size': 'lg'});return false;";
      $filename = wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/'.$key.'/config/settings.yml';
      $deprecated = '';
      $plugin_settings = null;
      if(wfFilesystem::fileExist($filename)){
        $plugin_settings = wfFilesystem::loadYml($filename);
        if(wfArray::issetAndTrue($plugin_settings, 'deprecated')){
          $deprecated = 'deprecated';
        }
      }
      $td = array();
      $td[] = wfDocument::createHtmlElement('td', $key);
      $td[] = wfDocument::createHtmlElement('td', $deprecated);
      $tr[] = wfDocument::createHtmlElement('tr', $td, array('onclick' => $onclick));
    }
    $tbody = wfDocument::createHtmlElement('tbody', $tr);
    $table->set('innerHTML', array($thead, $tbody));
    $element[] = $table->get();
    $element[] = wfDocument::createWidget('datatable/datatable_1_10_18', 'run', array('id' => 'table_plugin', 'json' => array('paging' => true, 'iDisplayLength' => 10, 'ordering' => true, 'info' => true, 'searching' => true, 'order' => array(array('0', 'asc')))));
    return $element;
  }
  /**
   * Get response code.
   * @param string $url
   * @return string
   */
  private function get_http_response_code($url) {
    $headers = get_headers($url);
    return wfPhpfunc::substr($headers[0], 9, 3);
  }
  /**
   * Plugin view page.
   */
  public function page_pluginview(){
    wfPlugin::includeonce('wf/yml');
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
    wfPlugin::includeonce($plugin);
    $element[] = wfDocument::createHtmlElement('h2', $plugin);
    /**
     * Public folder.
     */
    if(wfFilesystem::fileExist(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/'.$plugin.'/public')){
      $alert_public_folder = new PluginWfYml(__DIR__.'/element/alert_public_folder.yml');
      $alert_public_folder->setById('public_folder', 'innerHTML', wfArray::get($GLOBALS, 'sys/web_dir').'/'.$plugin);
      /**
       * If public folder exist we check if mandatory file readme.txt exist.
       */
      if(wfFilesystem::fileExist(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/'.$plugin.'/public/readme.txt')){
        $alert_public_folder->setById('readme_exist', 'innerHTML', 'Yes');
        if($this->get_http_response_code(wfSettings::getHttpAddress(true).'/plugin/'.$plugin.'/readme.txt') != "200"){
          $alert_public_folder->setById('readme_exist_public', 'innerHTML', 'No');
          $alert_public_folder->setById('actions_needed', 'innerHTML', 'Link, copy or move the folder to public directory.');
        }else{
          $alert_public_folder->setById('readme_exist_public', 'innerHTML', 'Yes');
          $alert_public_folder->setById('actions_needed', 'innerHTML', 'All is fine.');
        }
      }else{
        $alert_public_folder->setById('readme_exist', 'innerHTML', 'No');
        $alert_public_folder->setById('readme_exist_public', 'innerHTML', '-');
        $alert_public_folder->setById('actions_needed', 'innerHTML', 'Maybe! The plugin does not have the readme.txt file but could work anyway if it´s linked in a proper way?');
      }
      $element[] = $alert_public_folder->get();
    }
    /**
     * 
     */
    $item = array();
    $item[] = array('innerHTML' => 'Methods', 'active' => true);
    $class = wfArray::get($GLOBALS, 'sys/class');
    $rc = self::getReflectionClass(wfSettings::getPluginObj($plugin, false));
    $comment = self::cleanComment(wfArray::get($rc,'comment'));
    $comment = self::replace_load_tags($comment, $plugin);
    $element[] = wfDocument::createHtmlElement('div', $comment, array('class' => 'bg-primaryzzz', 'style' => 'font-familyzzz:courier new;border-radius:4px;'));
    if(wfArray::get($rc, 'methods')){
      foreach (wfArray::get($rc, 'methods') as $key2 => $value2) {
        $onclick = "PluginWfBootstrapjs.modal({id: 'wf_editor_methodview', url: '/$class/methodview?plugin=".urlencode($plugin)."&method=$key2', lable: 'Method', 'size': 'lg'});return false;";
        $item[] = array('href' => '#', 'innerHTML' => $key2, 'onclick' => $onclick);
      }
    }else{
      $item[] = array('href' => '#!', 'innerHTML' => '<i>No methods</i>');
    }
    $element[] = wfDocument::createWidget('wf/bootstrap', 'listgroup', array('item' => $item));
    $element[] = wfDocument::createHtmlElement('script', 'Prism.highlightAll();');
    $page = wfArray::set($page, 'content', $element);
    wfGlobals::setSys('layout_path', '/plugin/wf/editor/layout');
    wfDocument::mergeLayout($page);
  }
  /**
   * Method page.
   */
  public static function page_methodview(){
    wfPlugin::includeonce('wf/yml');
    $method = wfRequest::get('method');
    $page = wfFilesystem::loadYml(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/wf/editor/page/methodview.yml');
    $element = array();
    $plugin = urldecode(wfRequest::get('plugin'));
    wfPlugin::includeonce($plugin);
    $element[] = wfDocument::createHtmlElement('h2', $plugin);
    $class = wfSettings::getPluginObj($plugin, false);
    $rc = self::getReflectionClass($class);
    foreach (wfArray::get($rc, 'methods') as $key2 => $value2) {
      if($key2 != $method){
        /**
         * Only one method is of interest.
         */
        continue;
      }
      $class = 'bg-danger';
      $name = $key2;
      $type = null;
      if(wfPhpfunc::substr($key2, 0, 7)=='widget_'){
        $class = 'bg-warning';
        $name = wfPhpfunc::substr($name, 7);
        $type = 'widget';
      }elseif(wfPhpfunc::substr($key2, 0, 6)=='event_'){
        $class = 'bg-info';
        $name = wfPhpfunc::substr($name, 6);
        $type = 'event';
      }elseif(wfPhpfunc::substr($key2, 0, 5)=='page_'){
        $class = 'bg-success';
        $name = wfPhpfunc::substr($name, 5);
        $type = 'page';
      }else{
        $class = 'bg-successzzz';
      }
      $default = new PluginWfYml(wfArray::get($GLOBALS, 'sys/app_dir').'/plugin/'.$plugin.'/default/'.$type.'.'.$name.'.yml');
      $default_yml = null;
      if($default->get()){
        $default_yml = wfDocument::createHtmlElement('pre', array(
        wfDocument::createHtmlElement('span', 'Default values', array('style' => 'margin-left:40%;', 'title' => 'Default values from file: '.'/plugin/'.$plugin.'/default/'.$type.'.'.$name.'.yml')),
          wfDocument::createHtmlElement('code', "\n".trim(wfHelp::getYmlDump($default->get())), array('class' => ' language-yaml'))
          ), array('class' => ' language-yaml'));
      }
      $comment = wfArray::get($value2,'comment');
      $code = "\n".$value2['code'];
      $id = wfPhpfunc::str_replace('/', '_', $plugin.'_'.$key2);
      $comment .= '<br><a href="#" data-bs-toggle="collapse" data-bs-target="#plugin_source_'.$id.'">Source code.</a>';
      $comment .= '<pre stylezzz="display:none" id="plugin_source_'.$id.'" class="collapse" aria-expanded="false"><span style="margin-left:50%;">PHP</span><code class=" language-php">'.$code.'</code></pre>';
      $comment = self::cleanComment($comment, true);
      $comment = self::replace_load_tags($comment, $plugin);
      $element[] = wfDocument::createHtmlElement('div', array(
        wfDocument::createHtmlElement('a', 'Add to element', array(
          'style' => 'float:right;display:none;', 
          'class' => 'btn btn-default',
          'id' => 'btn_widget_add',
          'onclick' => "document.getElementById('btn_widget_add_from_window').setAttribute('data_plugin', '$plugin');document.getElementById('btn_widget_add_from_window').setAttribute('data_widget', '$name');document.getElementById('btn_widget_add_from_window').click();return false;"
        )),
        wfDocument::createHtmlElement('i', $type),
        wfDocument::createHtmlElement('h3', $name),
        wfDocument::createHtmlElement('div', $comment),
        $default_yml
      ), array('class' => $class, 'style' => 'padding:10px;margin-top:4px;border-radius:4px;'));
      $element[] = wfDocument::createHtmlElement('div', '&nbsp;');
    }
    $element[] = wfDocument::createHtmlElement('script', 'Prism.highlightAll();');
    if($type == 'widget'){
      $element[] = wfDocument::createHtmlElement('script', 'if(document.getElementById("btn_widget")){document.getElementById("btn_widget_add").style.display="";}');
    }
    $page = wfArray::set($page, 'content', $element);
    wfGlobals::setSys('layout_path', '/plugin/wf/editor/layout');
    wfDocument::mergeLayout($page);
  }
  
  /**
   * Method to get reflection of class.
   */
  public static function getReflectionClass($class){
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
  /**
   * Method to get code.
   */
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
  /**
   * Method to clean up comment.
   */
  private static function cleanComment($comment, $justify = false){
    if($justify){
      /** 
       * Remove '  ' from left if exist.
       */
      $array = preg_split("/\r\n|\n|\r/", $comment);
      $temp = null;
      foreach ($array as $key => $value) {
        if(wfPhpfunc::substr($value, 0, 2)=='  '){
          $temp .= wfPhpfunc::substr($value, 2, wfPhpfunc::strlen($value))."\n";
        }else{
          $temp .= $value."\n";
        }
      }
      $comment = $temp;
    }
    $comment = wfPhpfunc::str_replace("#code-php#",        '<pre><span style="margin-left:50%;">PHP</span><code class=" language-php">',                  $comment);
    $comment = wfPhpfunc::str_replace("#code-yml#",        '<pre><span style="margin-left:50%;">YML</span><code class=" language-yaml">',                 $comment);
    $comment = wfPhpfunc::str_replace("#code-javascript#", '<pre><span style="margin-left:50%;">Javascript</span><code class=" language-javascript">',    $comment);
    $comment = wfPhpfunc::str_replace("#code-html#",       '<pre><span style="margin-left:50%;">HTML</span><code class=" language-markup">',              $comment);
    $comment = wfPhpfunc::str_replace("#code#",            '</code></pre>', $comment);
    $comment = wfPhpfunc::str_replace("/*", '', $comment);
    $comment = wfPhpfunc::str_replace("*/", '', $comment);
    $comment = wfPhpfunc::str_replace("*", '', $comment);
    return $comment;
  }
  
  /**
   * Method to handle load tags.
   */        
  private static function replace_load_tags($str, $plugin){
    for($i=0; $i<100; $i++){
      $start = strpos($str, "#load:");
      $end = strpos($str, ":load#");
      if($start && $end){
        $anchor = wfPhpfunc::substr($str, $start, $end-$start+6);
        $src = wfPhpfunc::substr($str, $start+6, $end-$start-6);
        $src = wfSettings::replaceDir($src);
        $src = wfPhpfunc::str_replace('[plugin]', $plugin, $src);
        $content = null;
        if(wfFilesystem::fileExist($src)){
          $content = file_get_contents($src);
          $content = wfPhpfunc::str_replace('<', '&lt;', $content);
        }else{
          $content = "Could not find file: $src";
        }
        $str = wfPhpfunc::str_replace($anchor, $content, $str);
      }else{
        break;
      }
    }
    return $str;
  }
  /**
   * Page to show theme to switch to.
   */
  public function page_theme(){
    wfPlugin::includeonce('wf/yml');
    wfPlugin::enable('wf/table');
    $page = new PluginWfYml(__DIR__.'/page/theme.yml');
    wfDocument::renderElement($page->get());
  }
  private function get_theme_data(){
    $data = array();
    foreach (wfFilesystem::getScandir(wfArray::get($GLOBALS, 'sys/app_dir').'/theme') as $value) {
      foreach (wfFilesystem::getScandir(wfArray::get($GLOBALS, 'sys/app_dir').'/theme/'.$value) as $value2) {
        $theme = urlencode( $value.'/'.$value2);
        $data[] = array('name' => $value.'/'.$value2, 'theme' => $theme);
      }
    }
    return $data;
  }
  public function page_theme_data(){
    wfPlugin::includeonce('datatable/datatable_1_10_18');
    $datatable = new PluginDatatableDatatable_1_10_18();
    $data = $this->get_theme_data();
    exit($datatable->set_table_data($data));
  }
  /**
   * Page to switch theme in the editor.
   */
  public function page_themeload(){
    $theme = urldecode(wfRequest::get('theme'));
    $_SESSION = wfArray::set($_SESSION, 'plugin/wf/editor/activetheme', $theme);
    $json = array('reload' => true);
    exit(json_encode($json));
  }
  /**
   * Move array value to another position.
   * @param array $array
   * @param string $key
   * @param int $position 1 to size of array.
   * @return array
   */
  private function move_to_position($array, $key, $position){
    if(!array_key_exists($key, $array)){
      /**
       *  The key does not exist.
       */
      return null; 
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
      /**
       *  Position was not relative to array.
       */
      return null;
    }
    return $new;
  }
}
