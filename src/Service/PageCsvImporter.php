<?php

namespace App\Service;

use App\Entity\Page;
use Doctrine\ORM\EntityManagerInterface;

class PageCsvImporter
{
	const BATCH_UPLOAD_SIZE = 1000;

	private $em; 

	private $projectDir;

	public function __construct($projectDir, EntityManagerInterface $em)
	{
		$this->projectDir = $projectDir;
        $this->em = $em;
	}

	/**
	 * @param integer $startLine
	 * @param array   $headers
	 * @return void 
	 */
	public function prepareProcessUrlImporter($startLine = 0, $headers = array())
	{
		$filePath = $this->projectDir . '/public/uploads/sample.csv';
		$this->processUrlImporter($filePath, $startLine, $headers);
	}

	/**
	 * @param string  $startLine
	 * @param integer $filePath
	 * @param array   $headers
	 * @return void 
	 */
	public function processUrlImporter($filePath, $startLine, $headers)
	{
        $file = new \SplFileObject($filePath, "r");
        $file->setFlags(\SplFileObject::SKIP_EMPTY);
        $file->seek($startLine);

        $errorRows = $uniqueUrls = array();
        $count = $uploadCount = 0;

        while (!$file->eof() && $count < self::BATCH_UPLOAD_SIZE) {
            $count++;
            $lineNo = $count + $startLine;
            $pageDetails = $file->fgetcsv(",");
            $currentLine = $file->current();
            $ignoreLine = $this->ignoreLine($currentLine, $pageDetails)	;
            if ((null !== $pageDetails && $pageDetails) && !$ignoreLine) {
                if(count($headers) == 0){
                    $headers = $pageDetails;
                    continue;
                }
                $pageData = array_combine($headers, $pageDetails);
                $url = isset($pageData['url']) ? $pageData['url'] : '';
                // validation if emplty url string is passed
                if(empty($url)) {
                    continue;
                }
                // validation if url is invalid
                if (isset($pageData['url']) && trim($pageData['url'])) {
                    $isValidUrl =  filter_var($url, FILTER_VALIDATE_URL);
                    if (!$isValidUrl) {
                      	continue;
                    }
                }
                // validation if duplicate urls is passed in a csv
                if (isset($pageData['url']) && trim($pageData['url'])) {
                    if (!isset($uniqueUrls[trim($pageData['url'])])) {
                        $uniqueUrls[trim($pageData['url'])] = true;
                    } else {
                        continue;
                    }
                }
                try {
                	// check if url already exists
                	$page = $this->em->getRepository(Page::class)->findBy(['url' => $url]);
                	if (!$page) {
                        $page = new Page;
                        $page->setUrl($url);
                        $page->setCreatedAt(new \DateTimeImmutable());
                        $uploadCount++;
                        $this->em->persist($page);
                	}
                }catch(\Exception $e){
                    
                }
            }
        }
        $this->em->flush();

        if (!$file->eof()) {
        	$startLine = ($startLine + $count) -1;
        	$this->prepareProcessUrlImporter($startLine, $headers);
        }
	}
	
	/**
	 * @param string $line
	 * @param array  $csvArray
	 * @return boolean
	 */
	private function ignoreLine($line, $csvArray)
    {
        if (strlen(trim($line)) == 0) {
            return true;
        }
        $firstChar = substr($line, 0, 1);
        $firstTwoChars = substr($line, 0, 2);
        if (!$csvArray) {
            $csvArray = array();
        } else {
            $csvArray = array_filter($csvArray);
        }

        if ($firstChar == "#" || $firstTwoChars == "\"#" || trim($line) == ""
                || count($csvArray) == 0) {
            return true;
        }
        return false;
    }
}
