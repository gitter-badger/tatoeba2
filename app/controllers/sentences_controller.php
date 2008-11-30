<?php
class SentencesController extends AppController{
	var $name = 'Sentences';
	
	var $components = array ('GoogleLanguageApi', 'Lucene');
	
	function beforeFilter() {
	    parent::beforeFilter(); 
		
		// setting actions that are available to everyone, even guests
	    $this->Auth->allowedActions = array('index','show','add','translate','save_translation','search', 'add_comment');
	}

	
	function index(){
		$this->set('sentences',$this->Sentence->find('all'));
	}
	
	function show($id){
		if($id == "random"){
			$resultMax = $this->Sentence->query('SELECT MAX(id) FROM sentences');
			$max = $resultMax[0][0]['MAX(id)'];
			
			$randId = rand(1, $max);
			$this->Sentence->id = $randId;
		}else{
			$this->Sentence->id = $id;
		}
		
		$this->set('sentence',$this->Sentence->read());
	}
	
	function add(){
		if(!empty($this->data)){
			// setting correctness of sentence
			if($this->Auth->user('group_id')){
				$this->data['Sentence']['correctness'] = Sentence::MAX_CORRECTNESS - $this->Auth->user('group_id');
			}else{
				$this->data['Sentence']['correctness'] = 1;
			}
			
			// detecting language
			$this->GoogleLanguageApi->text = $this->data['Sentence']['text'];
			$response = $this->GoogleLanguageApi->detectLang();
			
			if($response['language']){
				$this->data['Sentence']['lang'] = $this->GoogleLanguageApi->google2TatoebaCode($response['language']);
				// saving
				if($this->Sentence->save($this->data)){
					// Logs
					$this->data['SentenceLog']['sentence_id'] = $this->Sentence->id;
					$this->data['SentenceLog']['sentence_lang'] = $response['language'];
					$this->data['SentenceLog']['sentence_text'] = $this->data['Sentence']['text'];
					$this->data['SentenceLog']['action'] = 'insert';
					$this->data['SentenceLog']['user_id'] = $this->Auth->user('id');
					$this->data['SentenceLog']['datetime'] = date("Y-m-d H:i:s");
					$this->Sentence->SentenceLog->save($this->data);
					
					// Confirmation message
					$this->flash(
						__('Your sentence has been saved.',true), 
						'/sentences'
					);
				}
			}else{
				echo 'Problem with language detection';
			}
		}
	}
	
	function delete($id){
		// We log first
		$this->Sentence->id = $id;
		$tmp = $this->Sentence->read();
		$this->data['SentenceLog']['sentence_id'] = $id;
		$this->data['SentenceLog']['sentence_lang'] = $tmp['Sentence']['lang'];
		$this->data['SentenceLog']['sentence_text'] = $tmp['Sentence']['text'];
		$this->data['SentenceLog']['action'] = 'delete';
		$this->data['SentenceLog']['user_id'] = $this->Auth->user('id');
		$this->data['SentenceLog']['datetime'] = date("Y-m-d H:i:s");
		$this->Sentence->SentenceLog->save($this->data);
		
		// Then we delete
		$this->Sentence->del($id);
		$this->flash('The sentence #'.$id.' has been deleted.', '/sentences');
	}

	function edit($id){
		$this->Sentence->id = $id;
		if(empty($this->data)){
			$this->data = $this->Sentence->read();
		}else{
			if($this->Sentence->save($this->data)){				
				// Logs
				$this->data['SentenceLog']['sentence_id'] = $this->Sentence->id;
				$this->data['SentenceLog']['sentence_lang'] = $this->data['Sentence']['lang'];
				$this->data['SentenceLog']['sentence_text'] = $this->data['Sentence']['text'];
				$this->data['SentenceLog']['action'] = 'update';
				$this->data['SentenceLog']['user_id'] = $this->Auth->user('id');
				$this->data['SentenceLog']['datetime'] = date("Y-m-d H:i:s");
				$this->Sentence->SentenceLog->save($this->data);
				
				// Confirmation message
				$this->flash(
					__('The sentence has been updated',true),
					'/sentences/edit/'.$id
				);
			}
		}
	}
	
	function translate($id){
		$this->Sentence->id = $id;
		$this->set('sentence',$this->Sentence->read());
		$this->data['Sentence']['id'] = $id;
	}
	
	function save_translation(){
		if(!empty($this->data)){
			// If we want the "HasAndBelongsToMany" association to work, we need the two lines below :
			$this->Sentence->id = $this->data['Sentence']['id'];
			$this->data['Translation']['Translation'][] = $this->data['Sentence']['id'];
			
			// And this is because the translations are reciprocal :
			$this->data['InverseTranslation']['InverseTranslation'][] = $this->data['Sentence']['id'];
			
			$this->data['Sentence']['id'] = null; // so that it saves a new sentences, otherwise it's like editing
			
			// setting level of correctness
			if($this->Auth->user('group_id')){
				$this->data['Sentence']['correctness'] = Sentence::MAX_CORRECTNESS - $this->Auth->user('group_id');
			}else{
				$this->data['Sentence']['correctness'] = 1;
			}
			
			// detecting language
			$this->GoogleLanguageApi->text = $this->data['Sentence']['text'];
			$response = $this->GoogleLanguageApi->detectLang();
			$this->data['Sentence']['lang'] = $response['language'];
			
			if($this->Sentence->save($this->data)){
				// Logs
				$this->data['TranslationLogs']['sentence_id'] = $this->data['Translation']['Translation'][0];
				$this->data['TranslationLogs']['sentence_lang'] = $this->data['Sentence']['sentence_lang'];
				$this->data['TranslationLogs']['translation_id'] = $this->Sentence->id;
				$this->data['TranslationLogs']['translation_lang'] = $this->data['Sentence']['lang'];
				$this->data['TranslationLogs']['translation_text'] = $this->data['Sentence']['text'];
				$this->data['TranslationLogs']['action'] = 'insert';
				$this->data['TranslationLogs']['user_id'] = $this->Auth->user('id');
				$this->data['TranslationLogs']['datetime'] = date("Y-m-d H:i:s");
				$this->Sentence->TranslationLogs->save($this->data);
				
				// Confirmation message
				$this->flash(
					__('The translation has been saved',true),
					'/sentences'
				);
			}else{
				echo 'problem';
			}
		}
	}
	
	function search(){
		if(isset($this->params['url']['query'])){
			$this->pageTitle = __('Tatoeba search : ',true) . $this->params['url']['query'];
			$query = $this->params['url']['query'];
			
			$lucene_results = $this->Lucene->search($query);
			$sentences = array();
			
			foreach($lucene_results as $result){
				$sentence = $this->Sentence->findById($result['id']);
				$sentence['Score'] = $result['score'];
				$sentences[] = $sentence;
			}
			
			/*
			print_r($sentences);
			
			// would give something like this :
			Array ( 
				[0] => Array ( 
					[Sentence] => Array ( 
						[id] => 157 
						[lang] => en 
						[text] => "I can't think with that noise", she said as she stared at the typewriter. [F] 
						[correctness] => 
						[user_id] => 
						[created] => 
						[modified] => 
					) 
					[SuggestedModification] => Array ( ) 
					[SentenceLog] => Array ( ) 
					[TranslationLogs] => Array ( ) 
					[Translation] => Array ( ) 
					[InverseTranslation] => Array ( ) 
					[Score] => 1 
				) 
			)
			*/
			
			$this->set('query', urldecode($query));
			if($sentences != array()){
				$this->set('sentences', $sentences);
			}
		}else{
			$this->pageTitle = __('Tatoeba search',true);
		}
	}
}
?>