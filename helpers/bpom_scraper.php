<?php
/**
 * BPOM Scraper Helper
 * Provides functions to scrape BPOM Indonesia website for product data
 */

function bpom_search($query) {
    $url = "https://cekbpom.pom.go.id/search?query=" . urlencode($query);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; BPOMScraper/1.0)');
    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html) {
        return null;
    }

    $dom = new DOMDocument();
    @$dom->loadHTML($html);

    $xpath = new DOMXPath($dom);

    $results = [];

    // Example: parse product list from search results
    $items = $xpath->query("//div[contains(@class, 'product-list-item')]");

    foreach ($items as $item) {
        $nameNode = $xpath->query(".//h3", $item);
        $regNoNode = $xpath->query(".//span[contains(text(), 'Reg. No')]", $item);
        $categoryNode = $xpath->query(".//span[contains(text(), 'Category')]", $item);

        $name = $nameNode->length > 0 ? trim($nameNode->item(0)->textContent) : '';
        $regNo = $regNoNode->length > 0 ? trim(str_replace('Reg. No:', '', $regNoNode->item(0)->textContent)) : '';
        $category = $categoryNode->length > 0 ? trim(str_replace('Category:', '', $categoryNode->item(0)->textContent)) : '';

        $results[] = [
            'name' => $name,
            'registration_number' => $regNo,
            'category' => $category
        ];
    }

    return $results;
}

function bpom_get_details($registrationNumber) {
    $url = "https://cekbpom.pom.go.id/detail?regno=" . urlencode($registrationNumber);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; BPOMScraper/1.0)');
    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html) {
        return null;
    }

    $dom = new DOMDocument();
    @$dom->loadHTML($html);

    $xpath = new DOMXPath($dom);

    $details = [];

    // Example: parse product details
    $nameNode = $xpath->query("//h1[contains(@class, 'product-name')]");
    $categoryNode = $xpath->query("//div[contains(@class, 'product-category')]");
    $ingredientsNode = $xpath->query("//div[contains(@class, 'product-ingredients')]");

    $details['name'] = $nameNode->length > 0 ? trim($nameNode->item(0)->textContent) : '';
    $details['category'] = $categoryNode->length > 0 ? trim($categoryNode->item(0)->textContent) : '';
    $details['ingredients'] = $ingredientsNode->length > 0 ? trim($ingredientsNode->item(0)->textContent) : '';

    return $details;
}
