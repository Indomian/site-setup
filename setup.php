<?php
/**
 * Скрипт выполняющий упаковку сайта, а затем и его распаковку в полностью автоматическом режиме.
 * Система только запрашивает у пользователя данные для подключения упаковки, а затем для распаковки.
 */
define('SESSION_NAME','setupSession');

function _t($code) {
	global $arLang;
	if(isset($arLang[$code])) {
		return $arLang[$code];
	}
	return "'".$code."'";
}

class Render {
	public $result='';
	public $title='Site archive 0.2.0';

	private $headerRendered=false;
	/**
	 * @var Application
	 */
	private $application;

	public function __construct($parent) {
		$this->application=$parent;
	}

	public function _($msg) {
		return _t($msg);
	}

	//Common renders
	public function getMenu() {
		$arMenu=array(
			'welcome'=>_t('STEP_WELCOME'),
			'dumpDB'=>_t('STEP_DUMPDB'),
			'archiveSite'=>_t('STEP_ARCHIVE'),
			'uploadSite'=>array(
				'title'=>_t('STEP_UPLOAD'),
				'steps'=>array(
					'UploadFTPNavigate',
					'UploadFTPUpload'
				)
			),
			'setupWelcome'=>array(
				'title'=>_t('STEP_UNPACK'),
				'steps'=>array(
					'unpack'=>_t('STEP_UNPACK'),
					'MODXSetup'=>_t('STEP_MODXSetup'),
					'MODXConfig'=>_t('STEP_MODXConfig')
				)
			),
			'undumpDB'=>_t('STEP_UNDUMP'),
			'goodby'=>_t('STEP_GOODBY')
		);
		$result='';
		foreach($arMenu as $key=>$title) {
			if(is_array($title)) {
				$title['steps'][]=$key;
				$bNeedDropdown=false;
				$arSearch=array();
				foreach($title['steps'] as $subkey=>&$step) {
					if(is_numeric($subkey)) {
						$arSearch[]=strtolower($step);
					} else {
						$arSearch[]=strtolower($subkey);
						$bNeedDropdown=true;
					}
				}
				if($bNeedDropdown) {
					if(in_array($this->application->getStep(),$arSearch)) {
						$result.='<li class="dropdown active">';
					} else {
						$result.='<li class="dropdown">';
					}
					$result.='<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button">'.$title['title'].($bNeedDropdown?' <span class="caret"></span>':'').'</a>';
					$result.='<ul class="dropdown-menu" role="menu">';
					foreach($title['steps'] as $subkey=>&$step) {
						if(is_numeric($subkey)) {
							continue;
						} else {
							if($this->application->getStep()==$subkey) {
								$result.='<li class="active">';
							} else {
								$result.='<li>';
							}
							$result.='<a href="'.$this->application->getStepUrl($subkey).'">'.$step.'</a></li>';
						}
					}
					$result.='</ul>';
				} else {
					if(in_array($this->application->getStep(),$arSearch)) {
						$result.='<li class="active">';
					} else {
						$result.='<li class="">';
					}
					$result.='<a href="'.$this->application->getStepUrl($key).'">'.$title['title'].'</a>';
				}
				$result.='</li>';
			} else {
				if($this->application->getStep()==strtolower($key)) {
					$result.='<li class="active">';
				} else {
					$result.='<li>';
				}
				$result.='<a href="'.$this->application->getStepUrl($key).'">'.$title.'</a></li>';
			}
		}
		return $result;
	}

	public function getFieldId($name) {
		return 'field'.hash('crc32b',$name);
	}

	public function getTextField(Form $form,$key) {
		$id=get_class($form);
		$name=$id.'['.$key.']';
		$id=$this->getFieldId($name);
		$result='<div class="form-group"><label for="'.$id.'" class="col-sm-3 control-label">'.$form->getAttributeLabel($key).'</label><div class="col-sm-9">'.
			'<input type="text" class="form-control" id="'.$id.'" value="'.$form->$key.'" name="'.$name.'"></div></div>';
		return $result;
	}

	public function getTextareaField(Form $form,$key) {
		$id=get_class($form);
		$name=$id.'['.$key.']';
		$id=$this->getFieldId($name);
		$result='<div class="form-group"><label for="'.$id.'" class="col-sm-3 control-label">'.$form->getAttributeLabel($key).'</label><div class="col-sm-9">'.
			'<textarea class="form-control" id="'.$id.'" name="'.$name.'" rows="10">'.htmlentities($form->$key,ENT_QUOTES,'utf-8',false).'</textarea></div></div>';
		return $result;
	}

	public function getCheckboxField(Form $form,$key) {
		$id=get_class($form);
		$name=$id.'['.$key.']';
		$id='field'.hash('crc32b',$name);
		$result='<div class="form-group"><div class="col-sm-offset-3 col-sm-9"><div class="checkbox"><label><input type="checkbox" name="'.$name.'" value="1" '.($form->$key?'checked':'').'> '
			.$form->getAttributeLabel($key).'</label></div></div></div>';
		return $result;
	}

	//Block output renders
	public function header() {
		if($this->headerRendered) {
			return $this;
		}
		$this->result.=<<<PHP_EOT
<!DOCTYPE html>
<html><head> <meta http-equiv="content-type" content="text/html; charset=utf-8" /><title>{$this->title}</title><link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap-theme.min.css">
<script src="//code.jquery.com/jquery-1.11.0.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>
</head><body>
<nav class="navbar navbar-default" role="navigation"><div class="container-fluid"><div class="navbar-header">
	<a class="navbar-brand" href="{$this->application->getCurrentUrl()}">VPackMan</a>
</div><ul class="nav navbar-nav">{$this->getMenu()}</ul></div></nav><div class="container">
PHP_EOT;
		$this->headerRendered=true;
		return $this;
	}

	public function footer() {
		if(!$this->headerRendered) {
			$this->header();
		}
		$this->result.='</div></body></html>';
		return $this;
	}

	public function simplePage($title,$content,$nextStep='',$nextLink='') {
		if(!$this->headerRendered) {
			$this->header();
		}
		$content=str_replace(PHP_EOL,'</p><p>',$content);
		if(!empty($nextStep) || !empty($nextLink)) {
			$link=empty($nextLink)?$this->application->getStepUrl($nextStep):$nextLink;
		}
		$this->result.='<div class="jumbotron"><h1>'.$title.'</h1><p>'.$content.'</p>';
		if(!empty($link)) {
			$this->result.='<p><a class="btn btn-primary btn-lg" href="'.$link.'" role="button">'._t('NEXT').'</a></p>';
		}
		$this->result.='</div>';
		$this->footer();
		return $this;
	}

	public function htmlPage($content) {
		if(!$this->headerRendered) {
			$this->header();
		}
		$this->result.=$content;
		$this->footer();
		return $this;
	}

	public function iframe($url,$title='External actions',$nextStep='') {
		$this->header();
		$this->result.='<div class="panel panel-default"><div class="panel-heading"><h3 style="margin-top:10px;">'.$title;
		if(!empty($nextStep)) {
			$link=$this->application->getStepUrl($nextStep);
			$this->result.='<a href="'.$link.'" class="btn btn-primary pull-right">'._t('NEXT').'</a>';
		}
		$this->result.='</h3></div><div class="panel-body">'.
			'<p>'._t('Attention! All scripts in iframe is disabled. If you required to use them, please follow link and perform required actions. Then you can continue using setup script. Link: ').'<a href="'.$url.'" target="_blank">'.$url.'</a></p>'.
			'<iframe border="no" style="width:100%;height:100%;" src="'.$url.'" id="frameContainer" sandbox="allow-forms"></iframe></div></div>';
		$this->result.=<<<PHP_EOT
<script type="text/javascript">
$(document).ready(function(){
	var frame=$('#frameContainer');
	function resizeFrame() {
		var height=$(window).innerHeight();
		var frameHeight=frame.height();
		var parentHeight=frame.parent().parent().height()-frameHeight;
		frame.css('height',height-$('.navbar').outerHeight()-parentHeight-40);
	}

	$(window).resize(resizeFrame);

	resizeFrame();
});
</script>
PHP_EOT;
		$this->footer();
		return $this;
	}

	public function getPresetButton(Form $form,$arButton) {
		$id='btn'.hash('crc32b',json_encode($arButton));
		$result='<button type="button" class="btn btn-info" id="'.$id.'">'.$arButton['title'].'</button>';
		$result.='<script type="text/javascript">$(document).ready(function(){$("#'.$id.'").click(function(e){e.preventDefault();';
		foreach($arButton['data'] as $key=>$fieldVal) {
			$id=get_class($form);
			$name=$id.'['.$key.']';
			$fieldId=$this->getFieldId($name);
			$result.='$("#'.$fieldId.'").val("'.$fieldVal.'");';
		}
		$result.='})});</script>';
		return $result;
	}

	public function formWithActions(Form $form,$submitAction='',$nextStep='') {
		if(!$this->headerRendered) {
			$this->header();
		}
		$class=get_class($form);
		$formId='form'.hash('crc32b',$class);
		$this->result.='<div class="panel panel-default"><div class="panel-heading">'.$form->getTitle().'</div><div class="panel-body">'.
			'<form class="form-horizontal" role="form" id="'.$formId.'" action="" method="post"><div class="alert alert-warning hidden" role="alert"></div>';
		if($form->getHeaderMessage()!='') {
			$this->result.='<div class="alert alert-info">'.$form->getHeaderMessage().'</div>';
		}
		$arButtons=$form->getHeaderButtons();
		if(!empty($arButtons)) {
			$this->result.='<div class="form-group"><div class="col-sm-10 col-sm-offset-3"><div class="btn-group">';
			foreach($arButtons as $arButton) {
				if($arButton['type']=='fill') {
					$this->result.=$this->getPresetButton($form, $arButton);
				}
			}
			$this->result.='</div></div></div>';
		}
		foreach($form->attributesNames() as $key) {
			$type=$form->getAttributeType($key);
			if(method_exists($this,'get'.$type.'field')) {
				$methodName='get'.$type.'field';
				$this->result.=$this->$methodName($form,$key);
			} else {
				$this->result.=$this->getTextField($form,$key);
			}
		}
		$this->result.='<div class="form-group"><div class="col-sm-offset-3 col-sm-9"><button type="submit" class="btn btn-default">'._t('RUN_BUTTON').'</button>';
		if($nextStep!='') {
			$this->result.=' <a href="'.$this->application->getStepUrl($nextStep).'" class="btn btn-primary hidden" role="nextStepButton">'._t('NEXT_BUTTON').'</a>';
		}
		$this->result.='</div></div>';
		if(!empty($submitAction)) {
			$this->result.=<<<PHP_EOT
			<div class="alert alert-success hidden" role="success"></div>
			<div class="progress hidden"><div class="progress-bar progress-bar-striped active" data-max="100" data-current="0" role="progressbar" style="width: 0%"><span class="sr-only">0% Complete</span></div></div>
<script type="text/javascript">
$(document).ready(function(){
	var form;

	function setProgress(value) {
		var bar=form.find('div[role=progressbar]');
		if(value>0) {
			bar.parent().removeClass('hidden');
		}
		bar.attr('data-current',value);
		var pos=Math.round(value/parseInt(bar.attr('data-max'))*100);
		bar.css('width',pos+'%').find('span').text(value+'% Complete');
	}

	function incProgress(value) {
		var bar=form.find('div[role=progressbar]');
		var currentValue=parseInt(bar.attr('data-current'));
		value=value+currentValue;
		setProgress(value);
	}

	function stepCallback(data) {
		if(data.hasOwnProperty('error')) {
			form.find('input,button,select').attr('disabled',false);
			var alertBlock=form.find('div[role=alert]').append(data.error);
			if(data.hasOwnProperty('errorsList')) {
				for(var ii in data.errorsList) {
					if(data.errorsList.hasOwnProperty(ii)) {
						alertBlock.append('<br/>'+data.errorsList[ii]);
					}
				}
			}
			alertBlock.removeClass('hidden');
			setProgress(0);
		} else if(data.hasOwnProperty('success')) {
			incProgress(10);
			var successBlock=form.find('div[role=success]').removeClass('hidden').append(data.success+'<br/>');
			if(data.hasOwnProperty('location')) {
				document.location=data.location;
			} else if(data.hasOwnProperty('nextAction')) {
				successBlock.append('{$this->_('RUNNING')}<b>['+data.nextAction+']</b>: ');
				$.get('{$this->application->getScriptUrl()}',{'action':data.nextAction},stepCallback,'json');
			} else {
				setProgress(100);
				form.find('a[role=nextStepButton]').removeClass('hidden');
			}
		} else {
			alertBlock=form.find('div[role=alert]').append('Wrong request answer').removeClass('hidden');
		}
	}

	$('form#{$formId}').submit(function(e){
		e.preventDefault();
		form=$(this);
		form.find('div[role=alert]').html('').addClass('hidden');
		form.find('div[role=success]').html('').addClass('hidden');
		var data=form.serialize();
		form.find('input,button,select').attr('disabled',true);
		setProgress(10);
		var successBlock=form.find('div[role=success]').removeClass('hidden').append('{$this->_('RUNNING')}<b>[{$submitAction}]</b>: ');
		$.post('{$this->application->getActionUrl($submitAction)}',data,stepCallback,'json');
	});
});
</script>
PHP_EOT;

		}
		$this->result.='</form></div></div>';
		$this->footer();
		return $this;
	}

	public function render() {
		header('Content-type: text/html; charset=utf-8');
		echo $this->result;
	}
}

class Form {
	protected $errors=array();
	protected $title='Form';
	protected $headerMessage='';

	public function setAttributes($arValues) {
		$className=get_class($this);
		if(isset($arValues[$className])) {
			$this->setAttributes($arValues[$className]);
		} else {
			foreach($this->attributesNames() as $key) {
				if(isset($arValues[$key])) {
					$this->$key=$arValues[$key];
				}
			}
		}
	}

	public function getAttributeType($field) {
		return 'text';
	}

	public function setTitle($value) {
		$this->title=$value;
	}

	public function getTitle() {
		return $this->title;
	}

	public function getHeaderMessage() {
		return $this->headerMessage;
	}

	public function attributesNames() {
		$obReflection=new ReflectionClass($this);
		$properties=$obReflection->getProperties(ReflectionProperty::IS_PUBLIC);
		$arResult=array();
		foreach($properties as $obProperty) {
			$arResult[]=$obProperty->getName();
		}
		return $arResult;
	}

	public function getAttributes() {
		$arResult=array_flip($this->attributesNames());
		foreach($arResult as $key=>$nothing) {
			$arResult[$key]=$this->$key;
		}
		return $arResult;
	}

	public function addError($message,$field='') {
		$this->errors[]=(!empty($field)?'['.$field.'] ':'').$message;
	}

	public function getErrors() {
		return $this->errors;
	}

	public function hasErrors() {
		return !empty($this->errors);
	}

	public function getAttributeLabel($field) {
		return $field;
	}

	public function getHeaderButtons() {
		return array();
	}
}

/**
 * Class DBDumpForm creates model for mysql connection and check utility and
 * dump utility for creating DB dump.
 */
class DBDumpForm extends Form{
	const DB_MODE_CONSOLE=1;
	const DB_MODE_MYSQLI=2;

	public $login;
	public $password;
	public $name;
	public $host;

	protected $definedMode;

	/**
	 * Method tries to connect to DB using console utilities
	 * @return bool
	 */
	public function connectToConsole() {
		exec('mysqldump --help',$output,$return);
		if($return!=0) {
			return false;
		}
		$login=escapeshellarg($this->login);
		$password=escapeshellarg($this->password);
		$dbname=escapeshellarg($this->name);
		$host=escapeshellarg($this->host);
		exec('mysql --user='.$login.' --password='.$password.' --database='.$dbname.' --host='.$host,$output,$return);
		if($return!=0) {
			return false;
		}
		return true;
	}

	/**
	 * Method tries to connect to DB using mysqli functions
	 * @return bool
	 */
	public function connectToMySQLi() {
		if(!function_exists('mysqli_connect')) {
			return false;
		}
		$resource=@mysqli_connect($this->host,$this->login,$this->password,$this->name);
		if(!$resource) {
			return false;
		}
		if(!@mysqli_select_db($resource,$this->name)) {
			return false;
		}
		return true;
	}

	/**
	 * Method checks if it can connect to DB. First it check if it could be done in
	 * console mode (mysqldump utility) if not - checks if it can be done as mysqli connection
	 */
	public function check() {
		if($this->connectToConsole()) {
			$this->definedMode=self::DB_MODE_CONSOLE;
			return true;
		} elseif($this->connectToMySQLi()) {
			$this->definedMode=self::DB_MODE_MYSQLI;
			return true;
		} else {
			$this->addError('Cant connect to DB');
		}
		return false;
	}

	private function _dumpConsole($filename) {
		$login=escapeshellarg($this->login);
		$password=escapeshellarg($this->password);
		$dbname=escapeshellarg($this->name);
		$host=escapeshellarg($this->host);
		$query="mysqldump --user={$login} --password={$password} -n --host={$host} {$dbname} > {$filename}";
		exec($query,$output,$result);
		if($result!=0) {
			$this->addError('Cant create dump: '.join(PHP_EOL,$output));
			return false;
		}
		return true;
	}

	private function _dumpMysql($filename) {
		throw new Exception('Not implemented');
		return false;
	}

	public function makeDump($filename) {
		if($this->definedMode==self::DB_MODE_CONSOLE) {
			return $this->_dumpConsole($filename);
		} elseif($this->definedMode==self::DB_MODE_MYSQLI) {
			return $this->_dumpMysql($filename);
		}
		$this->addError('Wrong mode for dump process');
		return false;
	}

	public function getAttributeLabel($field) {
		return _t('DBDUMP_FIELD_'.$field);
	}

	/**
	 * Method tries to detect mysql access data based on current site file system
	 * @param $application
	 */
	public function initFromSystem(Application $application) {
		$path=$application->getScriptRoot();
		if(file_exists($path.'/core/config/config.inc.php')) {
			//MODX
			$content=file_get_contents($path.'/core/config/config.inc.php');
			if(preg_match('#^\$database_server.*=.*("|\')([^\'"]+)("|\');#im',$content,$matches) && empty($this->host)) {
				$this->host=$matches[2];
			}
			if(preg_match('#^\$database_user.*=.*("|\')([^\'"]+)("|\');#im',$content,$matches) && empty($this->login)) {
				$this->login=$matches[2];
			}
			if(preg_match('#^\$database_password.*=.*("|\')([^\'"]+)("|\');#im',$content,$matches) && empty($this->password)) {
				$this->password=$matches[2];
			}
			if(preg_match('#dbname=([^;]+);#im',$content,$matches) && empty($this->name)) {
				$this->name=$matches[1];
			}
			$this->headerMessage=_t('DBDUMP_MODX_DATA');
		} elseif(file_exists($path.'/bitrix/php_interface/dbconn.php')) {
			//Bitrix
			$content=file_get_contents($path.'/bitrix/php_interface/dbconn.php');
			if(preg_match('#^\$DBHost.*=.*("|\')([^\'"]+)("|\');#im',$content,$matches) && empty($this->host)) {
				$this->host=$matches[2];
			}
			if(preg_match('#^\$DBLogin.*=.*("|\')([^\'"]+)("|\');#im',$content,$matches) && empty($this->login)) {
				$this->login=$matches[2];
			}
			if(preg_match('#^\$DBPassword.*=.*("|\')([^\'"]+)("|\');#im',$content,$matches) && empty($this->password)) {
				$this->password=$matches[2];
			}
			if(preg_match('#^\$DBName.*=.*("|\')([^\'"]+)("|\');#im',$content,$matches) && empty($this->name)) {
				$this->name=$matches[2];
			}
			$this->headerMessage=_t('DBDUMP_BITRIX_DATA');
		}
	}
}

/**
 * Class DBDumpForm creates model for mysql connection and check utility and
 * dump utility for unpacking DB dump.
 */
class DBUndumpForm extends Form{
	const DB_MODE_CONSOLE=1;
	const DB_MODE_MYSQLI=2;

	public $login;
	public $password;
	public $name;
	public $host;

	protected $definedMode;

	/**
	 * Method tries to connect to DB using console utilities
	 * @return bool
	 */
	public function connectToConsole() {
		exec('mysqldump --help',$output,$return);
		if($return!=0) {
			return false;
		}
		$login=escapeshellarg($this->login);
		$password=escapeshellarg($this->password);
		$dbname=escapeshellarg($this->name);
		$host=escapeshellarg($this->host);
		exec('mysql --user='.$login.' --password='.$password.' --database='.$dbname.' --host='.$host,$output,$return);
		if($return!=0) {
			return false;
		}
		return true;
	}

	/**
	 * Method tries to connect to DB using mysqli functions
	 * @return bool
	 */
	public function connectToMySQLi() {
		if(!function_exists('mysqli_connect')) {
			return false;
		}
		$resource=@mysqli_connect($this->host,$this->login,$this->password,$this->name);
		if(!$resource) {
			return false;
		}
		if(!@mysqli_select_db($resource,$this->name)) {
			return false;
		}
		return true;
	}

	/**
	 * Method checks if it can connect to DB. First it check if it could be done in
	 * console mode (mysqldump utility) if not - checks if it can be done as mysqli connection
	 */
	public function check() {
		if($this->connectToConsole()) {
			$this->definedMode=self::DB_MODE_CONSOLE;
			return true;
		} elseif($this->connectToMySQLi()) {
			$this->definedMode=self::DB_MODE_MYSQLI;
			return true;
		} else {
			$this->addError('Cant connect to DB');
		}
		return false;
	}

	private function _dumpConsole($filename) {
		$login=escapeshellarg($this->login);
		$password=escapeshellarg($this->password);
		$dbname=escapeshellarg($this->name);
		$host=escapeshellarg($this->host);
		$query="mysql --user={$login} --password={$password} --host={$host} --database={$dbname} < {$filename}";
		exec($query,$output,$result);
		if($result!=0) {
			$this->addError('Cant import dump: '.join(PHP_EOL,$output));
			return false;
		}
		return true;
	}

	private function _dumpMysql($filename) {
		throw new Exception('Not implemented');
		return false;
	}

	public function getAttributeLabel($field) {
		return _t('UNDUMP_FIELD_'.$field);
	}

	public function makeUndump($filename) {
		if($this->definedMode==self::DB_MODE_CONSOLE) {
			return $this->_dumpConsole($filename);
		} elseif($this->definedMode==self::DB_MODE_MYSQLI) {
			return $this->_dumpMysql($filename);
		}
		$this->addError('Wrong mode for undump process');
		return false;
	}

	/**
	 * Method tries to detect mysql access data based on current site file system
	 * @param $application
	 */
	public function initFromSystem(Application $application) {
		$path=$application->getScriptRoot();
		if(file_exists($path.'/core/config/config.inc.php')) {
			//MODX
			$content=file_get_contents($path.'/core/config/config.inc.php');
			if(preg_match('#^\$database_server.*=.*("|\')([^\'"]+)("|\');#im',$content,$matches) && empty($this->host)) {
				$this->host=$matches[2];
			}
			if(preg_match('#^\$database_user.*=.*("|\')([^\'"]+)("|\');#im',$content,$matches) && empty($this->login)) {
				$this->login=$matches[2];
			}
			if(preg_match('#^\$database_password.*=.*("|\')([^\'"]+)("|\');#im',$content,$matches) && empty($this->password)) {
				$this->password=$matches[2];
			}
			if(preg_match('#dbname=([^;]+);#im',$content,$matches) && empty($this->name)) {
				$this->name=$matches[1];
			}
			$this->headerMessage=_t('DBDUMP_MODX_DATA');
		} elseif(file_exists($path.'/bitrix/php_interface/dbconn.php')) {
			//Bitrix
			$content=file_get_contents($path.'/bitrix/php_interface/dbconn.php');
			if(preg_match('#^\$DBHost.*=.*("|\')([^\'"]+)("|\');#im',$content,$matches) && empty($this->host)) {
				$this->host=$matches[2];
			}
			if(preg_match('#^\$DBLogin.*=.*("|\')([^\'"]+)("|\');#im',$content,$matches) && empty($this->login)) {
				$this->login=$matches[2];
			}
			if(preg_match('#^\$DBPassword.*=.*("|\')([^\'"]+)("|\');#im',$content,$matches) && empty($this->password)) {
				$this->password=$matches[2];
			}
			if(preg_match('#^\$DBName.*=.*("|\')([^\'"]+)("|\');#im',$content,$matches) && empty($this->name)) {
				$this->name=$matches[2];
			}
			$this->headerMessage=_t('DBDUMP_BITRIX_DATA');
		}
	}
}

class ArchiveForm extends Form {
	const MODE_CONSOLE=1;
	const MODE_PHAR=2;

	public $exclude="core/cache/\n_build/\nassets/cache/Pic/\ncore/packages/*/";

	protected $definedMode;

	/**
	 * Method tries to connect to DB using console utilities
	 * @return bool
	 */
	public function connectToConsole() {
		exec('tar --help',$output,$return);
		if($return!=0) {
			return false;
		}
		return true;
	}

	public function getAttributeType($field) {
		if($field=='exclude') {
			return 'textarea';
		}
		return parent::getAttributeType($field);
	}

	public function connectToPhar() {
		return class_exists('PharData');
	}

	public function getExcludeList() {
		$arData=explode("\n",$this->exclude);
		foreach($arData as &$sLine) {
			$sLine=trim($sLine);
		}
		return $arData;
	}

	public function check() {
		if($this->connectToConsole()) {
			$this->definedMode=self::MODE_CONSOLE;
			return true;
		} elseif($this->connectToPhar()) {
			$this->definedMode=self::MODE_PHAR;
			return true;
		} else {
			$this->addError(_t('Cant find way to make archive'));
		}
		return false;
	}

	public function markExclude($filename) {
		$total=0;
		foreach($this->getExcludeList() as $sLine) {
			$path=$filename.DIRECTORY_SEPARATOR.str_replace(array('/','\\'),DIRECTORY_SEPARATOR,$sLine);
			$starPos=strpos($path,'*');
			if($starPos===false) {
				if(file_exists($path) && is_dir($path) && !file_exists($path.DIRECTORY_SEPARATOR.'CACHEDIR.TAG')) {
					if(!@touch($path.DIRECTORY_SEPARATOR.'CACHEDIR.TAG')) {
						$this->addError(_t('Cant mark directory: ').$path);
					}
					$total++;
				}
			} else {
				$prePath=rtrim(substr($path,0,$starPos),DIRECTORY_SEPARATOR);
				$afterPath=substr($path,$starPos+2);
				$arFiles=scandir($prePath);
				foreach($arFiles as $file) {
					if($file=='.') continue;
					if($file=='..') continue;
					$subPath=$prePath.DIRECTORY_SEPARATOR.$file;
					if(is_dir($subPath) && file_exists($subPath)) {
						$total++;
						if(!file_exists($subPath.DIRECTORY_SEPARATOR.'CACHEDIR-ALL.TAG')) {
							if(!@touch($subPath.DIRECTORY_SEPARATOR.'CACHEDIR-ALL.TAG')) {
								$this->addError(_t('Cant mark directory: ').$subPath);
							}
						}
					}
				}
			}
		}
		return $total;
	}

	public function _archiveConsole($filename) {
		$temp_file = tempnam(sys_get_temp_dir(), 'archive');
		if($temp_file=='') {
			$dirname=ini_get('upload_tmp_dir');
			$temp_file = tempnam($dirname, 'archive');
		}
		$query='tar -czf '.$temp_file.' ./ --exclude=\'setup.php\' --exclude-vcs --exclude-tag=\'CACHEDIR.TAG\' --exclude-tag-all=\'CACHEDIR-ALL.TAG\' 2>&1';
		exec($query,$output,$result);
		if($result==0) {
			@chmod($temp_file,0644);
			if(@rename($temp_file,$filename)) {
				return true;
			} else {
				@unlink($temp_file);
				$this->addError(_t('Cant move archive file: ').$temp_file._t(' to ').$filename);
				return false;
			}
		}
		$this->addError(_t('Error archiving site: ').join(PHP_EOL,$output));
		return false;
	}

	public function _archivePhar($filename) {
		return false;
	}

	public function makeArchive($filename) {
		if($this->definedMode==self::MODE_CONSOLE) {
			return $this->_archiveConsole($filename);
		} elseif($this->definedMode==self::MODE_PHAR) {
			return $this->_archivePhar($filename);
		}
		$this->addError(_t('Wrong mode for archive'));
		return false;
	}

	public function getAttributeLabel($field) {
		return _t('ARCHIVE_FIELD_'.$field);
	}

	/**
	 * Method tries to detect mysql access data based on current site file system
	 * @param $application
	 */
	public function initFromSystem(Application $application) {
		$path=$application->getScriptRoot();
		if(file_exists($path.'/core/config/config.inc.php')) {
			//MODX
			$this->exclude="core/cache/\n_build/\nassets/cache/Pic/\ncore/packages/*/";
			$this->headerMessage=_t('ARCHIVESITE_MODX_DATA');
		} elseif(file_exists($path.'/bitrix/php_interface/dbconn.php')) {
			$this->exclude="bitrix/cache/\nbitrix/managed_cache/\nbitrix/stack_cache/";
			$this->headerMessage=_t('ARCHIVESITE_BITRIX_DATA');
		}
	}
}

class ClearForm extends Form {
	const MODE_CONSOLE=1;
	const MODE_PHAR=2;

	protected $definedMode;
	public $exclude='';

	/**
	 * Method tries to connect to DB using console utilities
	 * @return bool
	 */
	public function connectToConsole() {
		exec('rm --help',$output,$return);
		if($return!=0) {
			return false;
		}
		return true;
	}

	public function check() {
		if($this->connectToConsole()) {
			$this->definedMode=self::MODE_CONSOLE;
			return true;
		} elseif($this->connectToPhar()) {
			$this->definedMode=self::MODE_PHAR;
			return true;
		} else {
			$this->addError(_t('Cant find way to make archive'));
		}
		return false;
	}

	public function connectToPhar() {
		return class_exists('PharData');
	}

	public function getExcludeList() {
		$arData=explode("\n",$this->exclude);
		foreach($arData as &$sLine) {
			$sLine=trim($sLine);
		}
		return $arData;
	}

	public function _clearConsole($filename) {
		foreach($this->getExcludeList() as $sPath) {
			if(file_exists($filename.DIRECTORY_SEPARATOR.$sPath) && is_dir($filename.DIRECTORY_SEPARATOR.$sPath)) {
				$query="rm -r ".$filename.DIRECTORY_SEPARATOR.$sPath.DIRECTORY_SEPARATOR.'*';
				exec($query,$output,$result);
			}
		}
		return true;
	}

	public function _clear($filename) {
		return false;
	}

	public function makeClear($filename) {
		if($this->definedMode==self::MODE_CONSOLE) {
			return $this->_clearConsole($filename);
		} elseif($this->definedMode==self::MODE_PHAR) {
			return $this->_clear($filename);
		}
		$this->addError(_t('Wrong mode for clear'));
		return false;
	}

	public function getAttributeLabel($field) {
		return _t('CLEAR_FIELD_'.$field);
	}

	/**
	 * Method tries to detect mysql access data based on current site file system
	 * @param $application
	 */
	public function initFromSystem(Application $application) {
		$path=$application->getScriptRoot();
		if(file_exists($path.'/core/config/config.inc.php')) {
			//MODX
			$this->exclude="core/cache\nassets/cache/Pic";
			$this->headerMessage=_t('CLEAR_MODX_DATA');
		} elseif(file_exists($path.'/bitrix/php_interface/dbconn.php')) {
			$this->exclude="bitrix/cache\nbitrix/managed_cache\nbitrix/stack_cache";
			$this->headerMessage=_t('CLEAR_BITRIX_DATA');
		}
	}
}

class UnpackForm extends Form {
	const MODE_CONSOLE=1;
	const MODE_PHAR=2;

	public $redirect_to_modx_setup;
	public $delete_archive;
	public $auto_create_config;

	protected $definedMode;

	public function getAttributeType($attribute) {
		return 'checkbox';
	}

	/**
	 * Method tries to connect to DB using console utilities
	 * @return bool
	 */
	public function connectToConsole() {
		exec('tar --help',$output,$return);
		if($return!=0) {
			return false;
		}
		return true;
	}

	public function connectToPhar() {
		return class_exists('PharData');
	}

	public function check() {
		if($this->connectToConsole()) {
			$this->definedMode=self::MODE_CONSOLE;
			return true;
		} elseif($this->connectToPhar()) {
			$this->definedMode=self::MODE_PHAR;
			return true;
		} else {
			$this->addError('Cant find way to make archive');
		}
		return false;
	}

	public function _extractConsole($filename) {
		$path=dirname($filename);
		$query='cd '.$path.'; tar -xzf '.$filename.' --no-overwrite-dir 2>&1';
		exec($query,$output,$result);
		if($result==0) {
			return true;
		}
		$this->addError('Error unpacking site: '.join(PHP_EOL,$output));
		return false;
	}

	public function _extractPhar($filename) {
		return false;
	}

	public function getAttributeLabel($field) {
		return _t('UNPACK_FIELD_'.$field);
	}

	public function makeExtract($filename) {
		$result=false;
		if($this->definedMode==self::MODE_CONSOLE) {
			$result=$this->_extractConsole($filename);
		} elseif($this->definedMode==self::MODE_PHAR) {
			$result=$this->_extractPhar($filename);
		}
		if($result) {
			if($this->delete_archive) {
				@unlink($filename);
			}
			return true;
		}
		$this->addError('Wrong mode for unpack');
		return false;
	}
}

class MODXConfigForm extends Form {
	public $login;
	public $password;
	public $name;
	public $host;
	public $root;

	/**
	 * Method tries to connect to DB using mysqli functions
	 * @return bool
	 */
	public function connectToMySQLi() {
		if(!function_exists('mysqli_connect')) {
			return false;
		}
		$resource=@mysqli_connect($this->host,$this->login,$this->password,$this->name);
		if(!$resource) {
			return false;
		}
		if(!@mysqli_select_db($resource,$this->name)) {
			return false;
		}
		return true;
	}

	/**
	 * Method checks if it can connect to DB. First it check if it could be done in
	 * console mode (mysqldump utility) if not - checks if it can be done as mysqli connection
	 */
	public function check() {
		if($this->connectToMySQLi()) {
			return true;
		} else {
			$this->addError('Cant connect to DB');
		}
		return false;
	}

	public function getAttributeLabel($field) {
		return _t('MODXCONFIG_FIELD_'.$field);
	}

	/**
	 * Method tries to detect mysql access data based on current site file system
	 * @param $application
	 */
	public function initFromSystem(Application $application) {
		$path=$application->getScriptRoot();
		if(file_exists($path.'/core/config/config.inc.php')) {
			//MODX
			$content=file_get_contents($path.'/core/config/config.inc.php');
			if(preg_match('#^\$database_server.*=.*("|\')([^\'"]+)("|\');#im',$content,$matches) && empty($this->host)) {
				$this->host=$matches[2];
			}
			if(preg_match('#^\$database_user.*=.*("|\')([^\'"]+)("|\');#im',$content,$matches) && empty($this->login)) {
				$this->login=$matches[2];
			}
			if(preg_match('#^\$database_password.*=.*("|\')([^\'"]+)("|\');#im',$content,$matches) && empty($this->password)) {
				$this->password=$matches[2];
			}
			if(preg_match('#dbname=([^;]+);#im',$content,$matches) && empty($this->name)) {
				$this->name=$matches[1];
			}
			$this->headerMessage=_t('DBDUMP_MODX_DATA');
		}
		$this->root=$path;
	}

	private function processConfig($path,$arPaths,$pattern,$replace) {
		if(file_exists($path)) {
			$content=file_get_contents($path);
			$new=preg_replace($pattern,$replace,$content);
			if($new!=$content) {
				if(!@file_put_contents($path,$new)) {
					$this->addError('Cant save config file: '.$path);
					return false;
				} else{
					return true;
				}
			} else {
				return true;
			}
		}
		$this->addError('File not found: '.$path);
		return false;
	}

	public function writeConfig(Application $application) {
		$path=array(
			'root'=>$this->root.DIRECTORY_SEPARATOR,
			'core'=>$this->root.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR,
			'connectors'=>$this->root.DIRECTORY_SEPARATOR.'connectors'.DIRECTORY_SEPARATOR,
			'manager'=>$this->root.DIRECTORY_SEPARATOR.'manager'.DIRECTORY_SEPARATOR,
			'assets'=>$this->root.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR,
		);
		$bResult=true;
		$bResult&=$this->processConfig($path['root'].'config.core.php',$path,
			'#define(\'MODX_CORE_PATH\',\s*\'([\w\d\/_\-]+)\');#',
			'define(\'MODX_CORE_PATH\',\''.$path['core'].'\');');
		$bResult&=$this->processConfig($path['connectors'].'config.core.php',$path,
			'#define(\'MODX_CORE_PATH\',\s*\'([\w\d\/_\-]+)\');#',
			'define(\'MODX_CORE_PATH\',\''.$path['core'].'\');');
		$bResult&=$this->processConfig($path['manager'].'config.core.php',$path,
			'#define(\'MODX_CORE_PATH\',\s*\'([\w\d\/_\-]+)\');#',
			'define(\'MODX_CORE_PATH\',\''.$path['core'].'\');');
		//Main config
		$bResult&=$this->processConfig($path['core'].'config/config.inc.php',$path,
			'#\$database_server\s*=\s*\'([\w\d\.]+)\';#',
			'$database_server = \''.$this->host.'\';');
		$bResult&=$this->processConfig($path['core'].'config/config.inc.php',$path,
			'#\$database_user\s*=\s*\'([\w\d\.]+)\';#',
			'$database_user = \''.$this->login.'\';');
		$bResult&=$this->processConfig($path['core'].'config/config.inc.php',$path,
			'#\$database_password\s*=\s*\'.*\';#',
			'$database_password = \''.$this->password.'\';');
		$bResult&=$this->processConfig($path['core'].'config/config.inc.php',$path,
			'#\$dbase\s*=\s*\'([\w\d\.]+)\';#',
			'$dbase = \''.$this->name.'\';');
		$bResult&=$this->processConfig($path['core'].'config/config.inc.php',$path,
			'#\$database_dsn\s*=\s*\'.*\';#',
			'$database_dsn = \'mysql:host='.$this->host.';dbname='.$this->name.';charset=utf8\';');
		$bResult&=$this->processConfig($path['core'].'config/config.inc.php',$path,
			'#\$modx_core_path\s*=\s*\'([\w\d\/_\-]+)\';#',
			'$modx_core_path = \''.$path['core'].'\';');
		$bResult&=$this->processConfig($path['core'].'config/config.inc.php',$path,
			'#\$modx_processors_path\s*=\s*\'([\w\d\/_\-]+)\';#',
			'$modx_processors_path = \''.$path['core'].'model/modx/processors/\';');
		$bResult&=$this->processConfig($path['core'].'config/config.inc.php',$path,
			'#\$modx_connectors_path\s*=\s*\'([\w\d\/_\-]+)\';#',
			'$modx_connectors_path = \''.$path['connectors'].'\';');
		$bResult&=$this->processConfig($path['core'].'config/config.inc.php',$path,
			'#\$modx_manager_path\s*=\s*\'([\w\d\/_\-]+)\';#',
			'$modx_manager_path = \''.$path['manager'].'\';');
		$bResult&=$this->processConfig($path['core'].'config/config.inc.php',$path,
			'#\$modx_base_path\s*=\s*\'([\w\d\/_\-]+)\';#',
			'$modx_base_path = \''.$path['root'].'\';');
		$bResult&=$this->processConfig($path['core'].'config/config.inc.php',$path,
			'#\$modx_assets_path\s*=\s*\'([\w\d\/_\-]+)\';#',
			'$modx_assets_path = \''.$path['root'].'\';');
		return $bResult;
	}
}

class UploadForm extends Form {
	public $domain;
	public $ftp_host;
	public $ftp_port=21;
	public $ftp_login;
	public $ftp_password;

	protected $upload_path='';

	private $connection;

	private function connect() {
		if($this->connection) {
			return;
		}
		$this->connection=ftp_connect($this->ftp_host,intval($this->ftp_port),20);
		if(!$this->connection) {
			throw new Exception('Cant connect to FTP server');
		}
		if(!ftp_login($this->connection,$this->ftp_login,$this->ftp_password)) {
			throw new Exception('Cant login to FTP server');
		}
		if(!ftp_pasv($this->connection,true)) {
			throw new Exception('Cant switch FTP server to passive mode');
		}
	}

	public function __destruct() {
		if($this->connection) {
			ftp_close($this->connection);
		}
	}

	public function check() {
		try {
			$this->connect();
			return true;
		} catch (Exception $e) {
			$this->addError($e->getMessage());
			return false;
		}
	}

	public function checkUpload() {
		try {
			$this->connect();
			if(!@ftp_chdir($this->connection,$this->upload_path)) {
				throw new Exception('Error switching directory: '.$this->upload_path);
			}
			return true;
		} catch (Exception $e) {
			$this->addError($e->getMessage());
			return false;
		}
	}

	public function makeUpload(Application $application) {
		$this->connect();
		if(!ftp_put($this->connection,$this->upload_path.'/setup.php',$application->getScriptRoot().DIRECTORY_SEPARATOR.'setup.php',FTP_BINARY)) {
			$this->addError('Error uploading setup script: setup.php');
			return false;
		}
		if(!ftp_put($this->connection,$this->upload_path.'/archive.tar.gz',$application->getScriptRoot().DIRECTORY_SEPARATOR.'archive.tar.gz',FTP_BINARY)) {
			$this->addError('Error uploading archive: archive.tar.gz');
			return false;
		}
		return true;
	}

	public function setUploadPath($upload_path) {
		$this->upload_path=$upload_path;
	}

	public function getUploadPath() {
		return $this->upload_path;
	}

	public function setAttributes($values) {
		parent::setAttributes($values);
		if(isset($values['uploadPath'])) {
			$this->setUploadPath($values['uploadPath']);
		}
	}

	public function getAttributes() {
		return array_merge(array(
			'uploadPath'=>$this->upload_path
		),parent::getAttributes());
	}

	public function getFileManager(Application $application) {
		$result=<<<PHP_EOT
<div class="panel panel-info">
	<div class="panel-heading">Выберите папку для загрузки</div>
	<div class="panel-body" id="files">
		<div class="alert alert-warning hidden" role="alert"></div>
		<div id="filesList">{$this->getFileList($application)}</div>
	</div>
</div>
<script type="text/javascript">
$(document).ready(function(){
	var block=$('#files');
	var filesList=$('#filesList');

	function stepCallback(data) {
		if(data.hasOwnProperty('error')) {
			block.find('a').removeClass('disabled');
			var alertBlock=block.find('div[role=alert]').append(data.error);
			if(data.hasOwnProperty('errorsList')) {
				for(var ii in data.errorsList) {
					if(data.errorsList.hasOwnProperty(ii)) {
						alertBlock.append('<br/>'+data.errorsList[ii]);
					}
				}
			}
			alertBlock.removeClass('hidden');
		} else if(data.hasOwnProperty('success')) {
			if(data.hasOwnProperty('location')) {
				document.location=data.location;
			} else {
				filesList.html(data.success);
				initHandlers(filesList);
			}
		} else {
			alertBlock=block.find('div[role=alert]').append('Wrong request answer').removeClass('hidden');
		}
	}

	function initHandlers(obDom) {
		obDom.find('a[role=linkPart]').click(function(e){
			e.preventDefault();
			filesList.find('a').addClass('disabled');
			var alertBlock=block.find('div[role=alert]').html('').addClass('hidden');
			$.post('{$application->getActionUrl('uploadFiles')}',{'part':$(this).attr('href')},stepCallback,'json');
		});
	}
	initHandlers(filesList);
});
</script>
PHP_EOT;
		return $result;
	}

	public function getFileList(Application $application) {
		$this->connect();
		if($this->upload_path=='') {
			$this->upload_path=ftp_pwd($this->connection);
		} else {
			if(!@ftp_chdir($this->connection,$this->upload_path)) {
				$this->addError('Error switching directory');
			}
		}
		$result='<p>Текущий путь: <div class="input-group"><input type="text" readonly value="'.$this->upload_path.'" class="form-control"/> <a href="'.$application->getStepUrl('uploadFTPUpload').'" class="btn btn-primary input-group-addon" name="select" style="color:white;">'._t('Select').'</a></div></p>';
		$arFiles=ftp_rawlist($this->connection,$this->upload_path);
		$result.='<div class="list-group">';
		if($this->upload_path!='/') {
			$result.='<a href=".." class="list-group-item" role="linkPart">..</a>';
		}
		if(is_array($arFiles)) {
			foreach($arFiles as $file) {
				$arFile=preg_split('#\s+#',$file);
				$isDirectory=strpos($arFile[0],'d')!==false;
				$file=join(' ',array_slice($arFile,8));
				if(!$isDirectory) continue;
				if($file=='.') continue;
				if($file=='..') continue;
				$result.='<a href="'.$file.'" class="list-group-item" role="linkPart"><span class="glyphicon glyphicon-folder-close"></span> '.$file.'</a>';
			}
		}
		$result.='</div>';
		return $result;
	}

	public function navigate($part) {
		$this->connect();
		if($part=='.') {
			return true;
		}
		if($part=='..') {
			$upload_path=dirname($this->upload_path);
		} else {
			if(strpos($part,'/')===false) {
				$upload_path=$this->upload_path.'/'.$part;
			} else {
				$upload_path=$part;
			}
		}
		$upload_path=str_replace('//','/',$upload_path);
		if(!@ftp_chdir($this->connection,$upload_path)){
			$this->upload_path=ftp_pwd($this->connection);
			$this->addError('Error switching directory');
		} else {
			$this->upload_path=$upload_path;
		}
		return !$this->hasErrors();
	}

	public function getAttributeLabel($field) {
		return _t('UPLOAD_FIELD_'.$field);
	}

	public function getHeaderButtons() {
		return array(
			'dev2'=>array(
				'title'=>'dev2.vortex',
				'type'=>'fill',
				'data'=>array(
					'domain'=>'.dev2.vortex',
					'ftp_host'=>'dev2.vortex',
					'ftp_port'=>21,
					'ftp_login'=>'www-data',
					'ftp_password'=>'Pjd3TqFK'
				)
			),
			'red'=>array(
				'title'=>'red.hosting.webvortex.ru',
				'type'=>'fill',
				'data'=>array(
					'domain'=>'',
					'ftp_host'=>'red.hosting.webvortex.ru',
					'ftp_port'=>21,
					'ftp_login'=>'',
					'ftp_password'=>''
				)
			)
		);
	}
}

class Storage {
	public function __construct() {
		session_name(SESSION_NAME);
		session_start();
	}

	public function save(Form $form) {
		$id=get_class($form);
		$_SESSION[$id]=$form->getAttributes();
	}

	public function load(Form &$form) {
		$id=get_class($form);
		if(isset($_SESSION[$id])) {
			$form->setAttributes($_SESSION[$id]);
		}
	}

	public function clear() {
		$_SESSION=array();
	}
}

class Application {
	const ERROR_COMMON=1;
	const ERROR_WRONG_REQUEST=2;
	const ERROR_ACTION=3;

	private $step;
	private $render;
	private $storage;

	public function __construct() {
		$this->render=new Render($this);
		$this->storage=new Storage();
	}

	public function getScriptUrl() {
		return parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
	}

	public function getCurrentUrl() {
		return $this->getScriptUrl().'?step='.$this->step;
	}

	public function getStepUrl($step) {
		if(method_exists($this,'step'.$step)) {
			return $this->getScriptUrl().'?step='.$step;
		}
		return $this->getScriptUrl();
	}

	public function getScriptRoot() {
		return dirname(__FILE__);
	}

	public function getActionUrl($action) {
		if(method_exists($this,'action'.$action)) {
			return $this->getScriptUrl().'?action='.$action;
		}
		return $this->getStepUrl('welcome');
	}

	public function getStep() {
		return $this->step;
	}

	public function ajaxError($message,$code=1,$list=array()) {
		$arResult=array(
			'error'=>nl2br($message),
			'errorCode'=>$code
		);
		if(!empty($list)) {
			foreach($list as &$sLine) {
				$sLine=nl2br($sLine);
			}
			$arResult['errorsList']=$list;
		}
		echo json_encode($arResult);
		return true;
	}

	public function ajaxSuccess($message,$nextAction='') {
		$arResult=array(
			'success'=>$message,
		);
		if(!empty($nextAction)) {
			$arResult['nextAction']=$nextAction;
		}
		echo json_encode($arResult);
		return true;
	}

	public function ajaxStep($step,$message='Redirect...') {
		$arResult=array(
			'success'=>_t($message),
			'location'=>$this->getStepUrl($step)
		);
		echo json_encode($arResult);
		return true;
	}

	public function ajaxRedirect($url,$message='Redirect to url...') {
		$arResult=array(
			'success'=>_t($message),
			'location'=>$url
		);
		echo json_encode($arResult);
		return true;
	}

	public function redirect($url) {
		header('Location: '.$url);
		die();
	}

	public function isAjaxRequest() {
		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']==='XMLHttpRequest';
	}

	public function isPostRequest() {
		return isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD']=='POST' && isset($_POST) && is_array($_POST);
	}

	/* ---------------------------------------- STEPS BLOCK --------------------------------------- */

	/**
	 *
	 */
	public function stepWelcome() {
		$this->storage->clear();
		$this->render->simplePage(_t('WELCOME_TITLE'),_t('WELCOME_TEXT'),'dumpDB')->render();
	}

	public function stepDumpDB() {
		$obForm=new DBDumpForm();
		$obForm->initFromSystem($this);
		$obForm->setTitle(_t('DBDUMP_FORM_TITLE'));
		$this->storage->load($obForm);
		$this->render->formWithActions($obForm,'dumpDBCheck','archiveSite')->render();
	}

	public function stepArchiveSite() {
		$obForm=new ArchiveForm();
		$obForm->initFromSystem($this);
		$obForm->setTitle(_t('ARCHIVE_FORM_TITLE'));
		$this->storage->load($obForm);
		$this->render->formWithActions($obForm,'archiveCheck','uploadSite')->render();
	}

	public function stepUploadSite() {
		$obForm=new UploadForm();
		$obForm->setTitle(_t('UPLOAD_FORM_TITLE'));
		$this->storage->load($obForm);
		$this->render->formWithActions($obForm,'uploadCheck')->render();
	}

	public function stepUploadFTPNavigate() {
		$obForm=new UploadForm();
		$this->storage->load($obForm);
		$content=$obForm->getFileManager($this);
		$this->storage->save($obForm);
		$this->render->htmlPage($content)->render();
	}

	public function stepUploadFTPUpload() {
		$obForm=new UploadForm();
		$obForm->setTitle(_t('UPLOAD_UPLOAD_TO_FORM_TITLE'));
		$this->storage->load($obForm);
		$this->render->formWithActions($obForm,'uploadPreUpload','goOuter')->render();
	}

	public function stepGoOuter() {
		$obForm=new UploadForm();
		$this->storage->load($obForm);
		$link='http://'.$obForm->domain.'/setup.php?step=setupWelcome';
		$this->render->simplePage(_t('GOTO_TITLE'),_t('GOTO_TEXT'),'',$link)->render();
	}

	public function stepSetupWelcome() {
		$this->render->simplePage(_t('WELCOME_SETUP_TITLE'),_t('WELCOME_SETUP_TEXT'),'unpack')->render();
	}

	public function stepUnpack() {
		$obForm=new UnpackForm();
		$obForm->setTitle(_t('UNPACK_FORM_TITLE'));
		$this->storage->load($obForm);
		$this->render->formWithActions($obForm,'unpackCheck','goMODX')->render();
	}

	public function stepMODXSetup() {
		$this->render->iframe('/setup/index.php',_t('MODX_SETUP_FORM_TITLE'),'UndumpDB')->render();
	}

	public function stepMODXConfig() {
		$obForm=new MODXConfigForm();
		$obForm->setTitle(_t('MODXCONFIG_FORM_TITLE'));
		$obForm->initFromSystem($this);
		$this->storage->load($obForm);
		$this->render->formWithActions($obForm,'MODXConfigCheck','UndumpDB')->render();
	}

	public function stepUndumpDB() {
		$obForm=new DBUndumpForm();
		$obForm->initFromSystem($this);
		$obForm->setTitle(_t('UNDUMP_FORM_TITLE'));
		$this->storage->load($obForm);
		$this->render->formWithActions($obForm,'undumpDBCheck','goodby')->render();
	}

	public function stepGoodby() {
		$this->render->simplePage(_t('DONE_TITLE'),_t('DONE_TEXT'),'Clear')->render();
	}

	public function stepClear() {
		$obForm=new ClearForm();
		$obForm->check();
		$obForm->initFromSystem($this);
		$obForm->makeClear($this->getScriptRoot());
		$this->redirect('/');
	}

	/* ---------------------------------------- ACTIONS BLOCK --------------------------------------- */
	public function actionDumpDBCheck() {
		$obForm=new DBDumpForm();
		$this->storage->load($obForm);
		if(!$this->isPostRequest()) {
			return $this->ajaxError(_t('No data send'),self::ERROR_WRONG_REQUEST);
		}
		$obForm->setAttributes($_POST);
		$this->storage->save($obForm);
		if($obForm->check()) {
			return $this->ajaxSuccess(_t('Connection to DB successfull'),'makeDump');
		} else {
			return $this->ajaxError('Error checking connection',self::ERROR_ACTION,$obForm->getErrors());
		}
	}

	public function actionMakeDump() {
		$obForm=new DBDumpForm();
		$this->storage->load($obForm);
		if($obForm->check()) {
			$filename=$this->getScriptRoot().DIRECTORY_SEPARATOR.'dump.sql';
			if(file_exists($filename)) {
				@unlink($filename);
			}
			$obForm->makeDump($filename);
			if(file_exists($filename)) {
				return $this->ajaxSuccess(_t('Dump successfully created: ').'<a href="dump.sql">dump.sql</a>');
			} else {
				return $this->ajaxError('Cant make dump',self::ERROR_ACTION,$obForm->getErrors());
			}
		} else {
			return $this->ajaxError('Error connecting to DB',self::ERROR_ACTION,$obForm->getErrors());
		}
	}

	public function actionArchiveCheck() {
		$obForm=new ArchiveForm();
		$this->storage->load($obForm);
		if(!$this->isPostRequest()) {
			return $this->ajaxError(_t('No data send'),self::ERROR_WRONG_REQUEST);
		}
		$obForm->setAttributes($_POST);
		$this->storage->save($obForm);
		if($obForm->check()) {
			return $this->ajaxSuccess(_t('Archivation available'),'archiveExclude');
		} else {
			return $this->ajaxError(_t('Error trying archive site'),self::ERROR_ACTION,$obForm->getErrors());
		}
	}

	public function actionArchiveExclude() {
		$obForm=new ArchiveForm();
		$this->storage->load($obForm);
		if($obForm->check()) {
			$excluded=$obForm->markExclude($this->getScriptRoot());
			if($obForm->hasErrors()) {
				return $this->ajaxError(_t('Error trying mark exclude directories'),self::ERROR_ACTION,$obForm->getErrors());
			} else {
				return $this->ajaxSuccess(_t('Excluded directories marked and ready: <b>').$excluded.'</b>','archiveSite');
			}
		} else {
			return $this->ajaxError(_t('Error trying mark exlude directories'),self::ERROR_ACTION,$obForm->getErrors());
		}
	}

	public function actionArchiveSite() {
		$obForm=new ArchiveForm();
		$this->storage->load($obForm);
		if($obForm->check()) {
			$filename=$this->getScriptRoot().DIRECTORY_SEPARATOR.'archive.tar.gz';
			if(file_exists($filename)) {
				@unlink($filename);
			}
			$obForm->makeArchive($filename);
			if(file_exists($filename)) {
				return $this->ajaxSuccess(_t('Site successfully packed: <a href="archive.tar.gz">archive.tar.gz</a>'));
			} else {
				return $this->ajaxError(_t('Error trying archive site: archive not found'),self::ERROR_ACTION,$obForm->getErrors());
			}
		} else {
			return $this->ajaxError(_t('Error trying archive site'),self::ERROR_ACTION,$obForm->getErrors());
		}
	}

	public function actionUploadCheck() {
		$obForm=new UploadForm();
		$this->storage->load($obForm);
		if(!$this->isPostRequest()) {
			return $this->ajaxError('No data send',self::ERROR_WRONG_REQUEST);
		}
		$obForm->setAttributes($_POST);
		$this->storage->save($obForm);
		if($obForm->check()) {
			return $this->ajaxSuccess(_t('Connection available'),'uploadSelectRoot');
		} else {
			return $this->ajaxError(_t('Error trying check connection'),self::ERROR_ACTION,$obForm->getErrors());
		}
	}

	public function actionUploadSelectRoot() {
		$this->ajaxStep('UploadFTPNavigate');
	}

	public function actionUploadFiles() {
		$obForm=new UploadForm();
		$this->storage->load($obForm);
		if(!$this->isPostRequest() || !isset($_POST['part'])) {
			return $this->ajaxError('No data send',self::ERROR_WRONG_REQUEST);
		}
		if($obForm->navigate($_POST['part'])) {
			$this->storage->save($obForm);
			return $this->ajaxSuccess($obForm->getFileList($this));
		} else {
			return $this->ajaxError('Error navigating FTP files',self::ERROR_ACTION,$obForm->getErrors());
		}
	}

	public function actionUploadPreUpload() {
		$obForm=new UploadForm();
		$this->storage->load($obForm);
		if(!$this->isPostRequest()) {
			return $this->ajaxError('No data send',self::ERROR_WRONG_REQUEST);
		}
		$obForm->setAttributes($_POST);
		$this->storage->save($obForm);
		if($obForm->checkUpload()) {
			return $this->ajaxSuccess(_t('Upload to selected folder is available'),'uploadUpload');
		} else {
			return $this->ajaxError(_t('Error trying check upload availability'),self::ERROR_ACTION,$obForm->getErrors());
		}
	}

	public function actionUploadUpload() {
		$obForm=new UploadForm();
		$this->storage->load($obForm);
		if($obForm->makeUpload($this)) {
			return $this->ajaxSuccess(_t('Upload successfull'));
		} else {
			return $this->ajaxError(_t('Error trying upload data'),self::ERROR_ACTION,$obForm->getErrors());
		}
	}

	public function actionUnpackCheck() {
		$obForm=new UnpackForm();
		$this->storage->load($obForm);
		if(!$this->isPostRequest()) {
			return $this->ajaxError(_t('No data send'),self::ERROR_WRONG_REQUEST);
		}
		$obForm->setAttributes($_POST);
		$this->storage->save($obForm);
		if($obForm->check()) {
			return $this->ajaxSuccess(_t('Unpack available'),'unpackUnpack');
		} else {
			return $this->ajaxError(_t('Unpack unavailable'),self::ERROR_ACTION,$obForm->getErrors());
		}
	}

	public function actionUnpackUnpack() {
		$obForm=new UnpackForm();
		$this->storage->load($obForm);
		$filename=$this->getScriptRoot().DIRECTORY_SEPARATOR.'archive.tar.gz';
		if($obForm->check()) {
			if($obForm->makeExtract($filename)) {
				if($obForm->auto_create_config) {
					return $this->ajaxRedirect($this->getStepUrl('MODXConfig'),'Redirecting to modx config create');
				} elseif($obForm->redirect_to_modx_setup) {
					return $this->ajaxRedirect($this->getStepUrl('MODXSetup'),'Redirecting to MODX setup');
				} else {
					return $this->ajaxRedirect($this->getStepUrl('undumpDb'),'Unpack successfull redirecting to DBUndump');
				}
			} else {
				return $this->ajaxError(_t('Unpack errors'),self::ERROR_ACTION,$obForm->getErrors());
			}
		} else {
			return $this->ajaxError(_t('Unpack prepare errors'),self::ERROR_ACTION,$obForm->getErrors());
		}
	}

	public function actionMODXConfigCheck() {
		$obForm=new MODXConfigForm();
		$this->storage->load($obForm);
		if(!$this->isPostRequest()) {
			return $this->ajaxError(_t('No data send'),self::ERROR_WRONG_REQUEST);
		}
		$obForm->setAttributes($_POST);
		$this->storage->save($obForm);
		if($obForm->check()) {
			return $this->ajaxSuccess(_t('Modx config available'),'MODXConfigWrite');
		} else {
			return $this->ajaxError(_t('Modx config unavailable'),self::ERROR_ACTION,$obForm->getErrors());
		}
	}

	public function actionMODXConfigWrite() {
		$obForm=new MODXConfigForm();
		$this->storage->load($obForm);
		if($obForm->check()) {
			if($obForm->writeConfig($this)) {
				return $this->ajaxSuccess('Configuration successfully updated');
			} else {
				return $this->ajaxError('Cant write configuration',self::ERROR_ACTION,$obForm->getErrors());
			}
		} else {
			return $this->ajaxError('Error checking config parameters',self::ERROR_ACTION,$obForm->getErrors());
		}
	}

	public function actionUndumpDBCheck() {
		$obForm=new DBUndumpForm();
		$this->storage->load($obForm);
		if(!$this->isPostRequest()) {
			return $this->ajaxError('No data send',self::ERROR_WRONG_REQUEST);
		}
		$obForm->setAttributes($_POST);
		$this->storage->save($obForm);
		if($obForm->check()) {
			return $this->ajaxSuccess('Connection to DB successfull','makeUndump');
		} else {
			return $this->ajaxError('Error checking connection',self::ERROR_ACTION,$obForm->getErrors());
		}
	}

	public function actionMakeUndump() {
		$obForm=new DBUndumpForm();
		$this->storage->load($obForm);
		if($obForm->check()) {
			$filename=$this->getScriptRoot().DIRECTORY_SEPARATOR.'dump.sql';
			if(!file_exists($filename)) {
				return $this->ajaxError('Dump file not found: '.$filename,self::ERROR_ACTION);
			}
			if($obForm->makeUndump($filename)) {
				return $this->ajaxSuccess('Dump successfully imported: <a href="dump.sql">dump.sql</a>');
			} else {
				return $this->ajaxError('Cant make undump',self::ERROR_ACTION,$obForm->getErrors());
			}
		} else {
			return $this->ajaxError('Error connecting to DB',self::ERROR_ACTION,$obForm->getErrors());
		}
	}


	public function run() {
		$this->step='welcome';
		if(isset($_GET['step']) && method_exists($this,'step'.$_GET['step'])) {
			$this->step=strtolower($_GET['step']);
			$methodName='step'.ucfirst($this->step);
			$this->$methodName();
		} elseif(isset($_GET['action']) && $this->isAjaxRequest() && method_exists($this,'action'.$_GET['action'])) {
			$methodName='action'.$_GET['action'];
			try {
				$this->$methodName();
			} catch(Exception $e) {
				$this->ajaxError($e->getMessage(),self::ERROR_ACTION);
			}
		} else {
			$this->stepWelcome();
		}
	}
}

//TEXTS SECTION
global $arLang;
$arLang=array(
	'RUNNING'=>'Выполняю: ',
	'STEP_WELCOME'=>'Начало',
	'STEP_DUMPDB'=>'Архив БД',
	'STEP_ARCHIVE'=>'Упаковка файлов',
	'STEP_UPLOAD'=>'Выгрузка архива',
	'STEP_UNPACK'=>'Распаковка архива',
	'STEP_UNDUMP'=>'Разворачивание базы',
	'STEP_MODXSetup'=>'Установка MODX',
	'STEP_MODXConfig'=>'Конфигурация MODX',
	'STEP_GOODBY'=>'Готово',
	'NEXT'=>'Далее',
	'RUN_BUTTON'=>'Выполнить',
	'NEXT_BUTTON'=>'Продолжить <span class="glyphicon glyphicon-arrow-right"></span>',

	'WELCOME_TITLE'=>'Упаковщик сайтов',
	'WELCOME_TEXT'=>'Упаковщик сайтов поможет вам сделать архив всего сайта, выполнить его загрузку на удалённый хостинг, а затем выполнить распаковку и установку сайта на удалённом хостинге.',

	'WELCOME_SETUP_TITLE'=>'Распаковщик сайтов',
	'WELCOME_SETUP_TEXT'=>'Распаковщик сайта, поможет вам распаковать загруженный архив и загрузить в базу данных взятый с собой архив БД.',

	'DBDUMP_MODX_DATA'=>'<i class="glyphicon glyphicon-info-sign"></i> Определил доступы по данным MODX',
	'DBDUMP_BITRIX_DATA'=>'<i class="glyphicon glyphicon-info-sign"></i> Определил доступы по данным Битрикс',
	'DBDUMP_FORM_TITLE'=>'Параметры подключения к БД',
	'DBDUMP_FIELD_login'=>'Имя пользователя',
	'DBDUMP_FIELD_password'=>'Пароль',
	'DBDUMP_FIELD_name'=>'Название базы',
	'DBDUMP_FIELD_host'=>'Хост для подключения',

	'ARCHIVESITE_BITRIX_DATA'=>'Каталоги для исключения определены для Битрикс',
	'ARCHIVESITE_MODX_DATA'=>'Каталоги для исключения определены для MODX',
	'ARCHIVE_FORM_TITLE'=>'Архивация файлов и каталогов',
	'ARCHIVE_FIELD_exclude'=>'Исключить следующие каталоги (по одному пути на строку)',

	'UPLOAD_FORM_TITLE'=>'Параметры выгрузки архива',
	'UPLOAD_FIELD_domain'=>'Адрес сайта выгрузки',
	'UPLOAD_FIELD_ftp_host'=>'Адрес FTP',
	'UPLOAD_FIELD_ftp_port'=>'Порт FTP',
	'UPLOAD_FIELD_ftp_login'=>'Логин FTP',
	'UPLOAD_FIELD_ftp_password'=>'Пароль FTP',
	'UPLOAD_UPLOAD_TO_FORM_TITLE'=>'Выбран каталог загрузки, пожалуйста нажмите кнопку "Выполнить"',

	'UNPACK_FORM_TITLE'=>'Распаковка архива на сайте',
	'UNPACK_FIELD_redirect_to_modx_setup'=>'После распаковки открыть окно установки MODX',
	'UNPACK_FIELD_delete_archive'=>'После распаковки удалить архив с сайтом',
	'UNPACK_FIELD_auto_create_config'=>'Автоматически сгенерировать файлы конфигурации для MODX',

	'GOTO_TITLE'=>'Всё готово к переходу',
	'GOTO_TEXT'=>'Мы всё упаковали и выгрузили на новую площадку. Мы готовы к завершению установки!',

	'MODX_SETUP_FORM_TITLE'=>'Установка MODX',

	'UNDUMP_FORM_TITLE'=>'Подключение к БД для распаковки дампа',
	'UNDUMP_FIELD_login'=>'Имя пользователя',
	'UNDUMP_FIELD_password'=>'Пароль',
	'UNDUMP_FIELD_name'=>'Название базы',
	'UNDUMP_FIELD_host'=>'Хост для подключения',

	'MODXCONFIG_FORM_TITLE'=>'Автоматическая конфигурация MODX без переустановки',
	'MODXCONFIG_FIELD_login'=>'Имя пользователя',
	'MODXCONFIG_FIELD_password'=>'Пароль',
	'MODXCONFIG_FIELD_name'=>'Название базы',
	'MODXCONFIG_FIELD_host'=>'Хост для подключения',
	'MODXCONFIG_FIELD_root'=>'Путь к корню сайта',

	'DONE_TITLE'=>'Всё готово!',
	'DONE_TEXT'=>'Спасибо, все операции выполнены. Не забудьте удалить файл setup.php с сайта. Если нажать кнопку "Далее" система очистит каталоги с кешем и удалит скрипт setup.php',

	'Connection to DB successfull'=>'Успешное соединение с БД',
	'Dump successfully created: '=>'Дамп базы успешно создан: ',
	'Archivation available'=>'Архивация доступна',
	'Error trying mark exclude directories'=>'Ошибка при отметке каталогов для исключения из архива',
	'Cant mark directory: '=>'Не удалось исключить каталог: ',
	'Excluded directories marked and ready: <b>'=>'Отмечено для исключения каталогов: <b>',
	'Site successfully packed: <a href="archive.tar.gz">archive.tar.gz</a>' => 'Сайт успешно упакован: <a href="archive.tar.gz">archive.tar.gz</a>',
	'Connection available'=>'Соединение доступно',
	'Redirect to url...'=>'Перенаправление...',
	'Select'=>'Выбрать',
	'Upload to selected folder is available'=>'Загрузка в выбранный каталог доступна',
	'Upload successfull'=>'Загрузка успешно выполнена',
	'Unpack available'=>'Распаковка доступна',
	'Redirecting to MODX setup'=>'Перенаправление на установку MODX',
	'Unpack successfull redirecting to DBUndump'=>'Распаковка успешна, переходим к загрузке дампа базы данных',
	'Attention! All scripts in iframe is disabled. If you required to use them, please follow link and perform required actions. Then you can continue using setup script. Link: '=>
		'Внимание! Все скрипты в IFRAME выключены. Если вам требуются скрипты, пожалуйста перейдите по ссылке и выполните процесс установки там. После этого вы сможете продолжить распаковку сайта. Ссылка: ',

);

$obApplication=new Application();
$obApplication->run();
