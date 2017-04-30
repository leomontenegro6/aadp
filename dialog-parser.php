<?php
require_once('utils/aade.php');

$filename = $_FILES['script-file']['name'];
$path = $_FILES['script-file']['tmp_name'];

$extension = explode('.', $filename);
$extension = strtolower( end($extension) );
$filename_without_extension = str_replace('.' . $extension, '', $filename);

if($extension != 'txt'){
	die('Formato inválido.');
}
if(!file_exists($path)){
	die('Erro ao carregar arquivo transferido para o servidor.');
}

$file = file($path);

// Separating strings in sections
$number = -1;
$sections = $sections_blocks = array();
foreach($file as $line){
	$checkDialogueChanged = preg_match('/\{\{[0-9]+\}\}/', $line);
	if($checkDialogueChanged){
		$expression = preg_match('/\{\{[0-9]+\}\}/', $line, $results);
		$number = str_replace(array('{', '}'), '', $results[0]);
		$number = (int)$number;
	}
	
	if($number > -1){
		$line = str_replace('{{' . $number . '}}', '', $line);
		
		if(!isset($sections[$number])){
			$sections[$number] = $line;
		} else {
			$sections[$number] .= $line;
		}
	}
}

$tag = false;
$character_code = $tag_text = '';
$i = 0;

// Iterating into sections to separate them into blocks
foreach($sections as $number=>$section){
	$chars_section = str_split($section);
	
	// Iterating current section, char by char
	foreach($chars_section as $char){
		if($char == '{'){
			$tag = true;
		} elseif($char == '}'){
			$tag = false;
		}
		
		if(!isset($sections_blocks[$number])){
			$sections_blocks[$number] = array();
		}
		if(!isset($sections_blocks[$number][$i])){
			$sections_blocks[$number][$i] = array();
		}
		if(!isset($sections_blocks[$number][$i]['character_code'])){
			$sections_blocks[$number][$i]['character_code'] = $character_code;
		}
		if(!isset($sections_blocks[$number][$i]['text'])){
			$sections_blocks[$number][$i]['text'] = $char;
		} else {
			$sections_blocks[$number][$i]['text'] .= $char;
		}
		
		if($tag){
			if($char != '{'){
				$tag_text .= $char;
			}
		} else {
			if(aade::startsWith($tag_text, 'name:')){
				$tmp = explode(':', $tag_text);
				$character_code = trim( end($tmp) );
				
				$sections_blocks[$number][$i]['character_code'] = $character_code;
			}
			
			$checkBreakDetected = in_array($tag_text, array('p', 'nextpage_button', 'nextpage_nobutton'));
			if($checkBreakDetected){
				$i++;
			}
			$tag_text = '';
		}
	}
}
?>
<table id="dialog-parser-table" class="table table-striped table-bordered" cellspacing="0" width="100%">
	<thead>
		<tr>
			<th>Seção</th>
			<th>Número</th>
			<th>Bloco</th>
			<th>Prévia</th>
			<th>Ações</th>
		</tr>
	</thead>
	<tbody>
		<?php
		$total_dialog_blocks = 0;
		$total_sections = 0;
		foreach($sections_blocks as $section_number=>$blocks){
			$total_sections++;
			foreach($blocks as $block_number=>$block){
				$total_dialog_blocks++;
				
				$textareaName = "dialog[{$section_number}][{$block_number}]";
				$dialogId = "s{$section_number}-b{$total_dialog_blocks}-dialog";
				
				$text = rtrim($block['text']);
				$characterCode = $block['character_code'];
				?>
				<tr>
					<td>{{<?php echo $section_number ?>}}</td>
					<td><?php echo $total_dialog_blocks ?></td>
					<td class="formFields">
						<textarea class="form-control text-field" name="<?php echo $textareaName ?>" rows="5" cols="100"
							onkeyup="aade.updatePreview(this, '<?php echo $dialogId ?>', 't', false)"><?php echo $text ?></textarea>		
					</td>
					<td>
						<div id="<?php echo $dialogId ?>" class="dialog-preview text-only">
							<div class="character-name" data-character-code="<?php echo $characterCode ?>"></div>
							<div class="text-window"></div>
						</div>
					</td>
					<td>
						<button class="btn btn-success copy-clipboard" title="Copiar para área de transferência"
							data-clipboard-text="teste">
							<span class="glyphicon glyphicon-copy"></span>
						</button>
					</td>
				</tr>
			<?php
			}
		} ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="4">
				Total de seções: <?php echo $total_sections ?> - Total de diálogos: <?php echo $total_dialog_blocks ?>
				<button class="btn btn-primary pull-right" title="Gerar script após as edições" type="button" onclick="aade.generateScript()">
					<span class="glyphicon glyphicon-file"></span>
					Gerar Script
				</button>
			</td>
		</tr>
	</tfoot>
</table>
<form id="dialog-parser-form" action="dialog-file-generate.php" method="post" target="_blank">
	<input type="hidden" name="filename" value="<?php echo $filename_without_extension ?>" />
</form>