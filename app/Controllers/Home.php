<?php namespace App\Controllers;

use App\Libraries\Pembobotan;
use App\Models\TesaurusModel;
use App\Libraries\Query;
use App\Libraries\Dokumen;

helper('form');

class Home extends BaseController
{
	function __construct() {						
		$this->pembobotan = Pembobotan::getInstance();
		$this->request = \Config\Services::request();
		$this->tesaurusModel = TesaurusModel::getInstance();
		$this->query = Query::getInstance();
		$this->dokumen = Dokumen::getInstance();		
	}

	public function index()
	{
		$model = new \App\Models\HasilModel();
		
		$model->orderBy('rangking','DESC');
		$data = [
			'documents' => $model->paginate(5, 'no'),
			'pager' => $model->pager
		];

		return view('index', $data);		
	}

	public function admin() 
	{
		$data['tesaurus'] = $this->tesaurusModel->findAll();		
		return view('admin/page', $data);
	}

	public function search() 
	{					
		$query = $this->query->queryExpansion($this->request->getVar('query'));		
		print_r($query);
		echo '<br/>';
		list($tfidf_query, $paVek_query) = $this->query->bobotQuery($query);	
		if (empty($tfidf_query)) {
			echo "Pencarian/kata kunci tidak ditemukan !!";
		} else {
			// print_r($tfidf_query);
			// echo '<br/>';		
			// echo $paVek_query;
			$tfidf_dokumen = $this->dokumen->getRelevanDokumen(array_keys($tfidf_query));		
		
			$bobot_dokumen = $this->pembobotan->cosineSimilarity($tfidf_query, $tfidf_dokumen, $paVek_query);
		
			$result = $this->dokumen->result($bobot_dokumen);
			
			return redirect()->to('search_engine/public/')->withInput();	
		}
		
			
	}

	public function hitungBobot()
	{
		$this->pembobotan->hitungBobot();
	}	

	public function tesaurus($action,$value=null)
	{
		switch ($action) {
			case 'form':
				$data['action'] = 'tambah';
				return view('admin/form-tesaurus', $data);
				break;					
			case 'tambah':
				$a = $this->tesaurusModel->builder()->ignore(true)->insert([
					'kata' => $this->request->getVar('kata'),
					'gugus_kata' => $this->request->getVar('gugus_kata')
				]);
				$status = ($a == 0) ? 'gagal' : 'sukses';
				return redirect()->to('form')->with('status', $status)->withInput();
				break;
			case 'edit':
				$data['lema'] = $this->tesaurusModel->find($this->request->getVar('id'));
				$data['action'] = 'update';				
				return view('admin/form-tesaurus', $data);
				break;
			case 'update':
				$this->tesaurusModel->where('kata', $this->request->getVar('kata'))->set(['gugus_kata' => $this->request->getVar('gugus_kata')])->update();
				$data['action'] = 'update';				
				return view('admin/form-tesaurus', $data);
				break;
			case 'delete':
				$this->tesaurusModel->where('id', $this->request->getVar('id'))->delete();
				return redirect('admin');
				break;
			default:
				throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
				break;
		}		
	}


	public function queryExpansion()
	{
		$query = $this->query->queryExpansion($this->request->getVar('query'));
		print_r($query);
		list($tfidf_query, $paVek_query) = $this->query->bobotQuery($query);
		echo '<br>';
		print_r($tfidf_query);
		echo '<br>';
		echo $paVek_query;		
		
		$tfidf_dokumen = $this->dokumen->getRelevanDokumen(array_keys($tfidf_query));		
		echo '<br>';
		print_r($tfidf_dokumen);
	}	
}