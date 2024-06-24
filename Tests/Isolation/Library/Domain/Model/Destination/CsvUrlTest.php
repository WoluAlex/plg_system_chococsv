<?php

/**
 * @copyright (c) 2009 - present. Mr Alexandre J-S William ELISÉ. All rights reserved.
 * @license       GNU Affero General Public License v3.0 or later (AGPL-3.0-or-later). See LICENSE.txt file
 */

namespace Tests\Isolation\Library\Domain\Model\Destination;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects


use AlexApi\Plugin\System\Chococsv\Library\Domain\Model\Destination\CsvUrl;
use org\bovigo\vfs\vfsStream;
use Tests\Isolation\IsolationTestCase;

final class CsvUrlTest extends IsolationTestCase
{
    private $vfsStream = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vfsStream = vfsStream::setup('media', '2770', [
            'com_chococsv' => [
                'data' => [
                    'sample-data.csv' => <<<'EOD'
id,tokenindex,access,title,alias,catid,articletext,introtext,fulltext,language,metadesc,metakey,state,featured,article-subform-field,images,urls
0,app-001,1,Datasource - Offline csv file – Sample Data : Line 2,line-2,2,Sample article text 2,Sample intro text 2,,*,,,1,0,"{""row0"":{""field2"":1410,""field1"":""This an article summary 1 for this dessert. <em>Short</em> & <em>Sweet!</em>"", ""field7"": ""What's up <strong>Super Joomlers!</strong> <em>Alex</em> here...Proud to be a joomler. Nowadays focusing on Joomla! 4.x Web Services Apis.""}, ""row1"":{""field2"":1410,""field1"":""This an article summary 2 for this dessert. <em>Short</em> & <em>Sweet!</em>"", ""field7"": ""What's up <strong>Super Joomlers!</strong> <em>Alex</em> here... Proud to be a joomler. Nowadays focusing on Joomla! 4.x Web Services Apis.""}, ""row2"":{""field2"":1410,""field1"":""This an article summary 3 for this dessert. <em>Short</em> & <em>Sweet!</em>"", ""field7"": ""What's up <strong>Super Joomlers!</strong> <em>Alex</em> here... Proud to be a joomler. Nowadays focusing on Joomla! 4.x Web Services Apis.""}}","{""image_intro"": ""/images/powered_by.png"", ""image_intro_caption"": ""sample intro image caption"", ""image_intro_alt"":""sample intro image alt text"", ""float_intro"":"""",""image_fulltext"": ""/images/powered_by.png"", ""image_fulltext_caption"": ""sample full image caption"", ""image_fulltext_alt"":""sample full image alt text"", ""float_fulltext"":""""}","{""urla"":""https://apiadept.com"",""urlatext"":""Website"",""targeta"":"""",""urlb"":""https://github.com/alexandreelise"",""urlbtext"":""Github"",""targetb"":"""",""urlc"":""https://twitter.com/mralexandrelise"",""urlctext"":""Twitter"",""targetc"":""""}"
EOD,
                    'other-data.csv' => <<<'EOD'
id,tokenindex,access,title,alias,catid,articletext,introtext,fulltext,language,metadesc,metakey,state,featured,article-subform-field,images,urls
0,app-001,1,Datasource - Offline csv file – Sample Data : Line 2,line-2,2,Sample article text 2,Sample intro text 2,,*,,,1,0,"{""row0"":{""field2"":1410,""field1"":""This an article summary 1 for this dessert. <em>Short</em> & <em>Sweet!</em>"", ""field7"": ""What's up <strong>Super Joomlers!</strong> <em>Alex</em> here...Proud to be a joomler. Nowadays focusing on Joomla! 4.x Web Services Apis.""}, ""row1"":{""field2"":1410,""field1"":""This an article summary 2 for this dessert. <em>Short</em> & <em>Sweet!</em>"", ""field7"": ""What's up <strong>Super Joomlers!</strong> <em>Alex</em> here... Proud to be a joomler. Nowadays focusing on Joomla! 4.x Web Services Apis.""}, ""row2"":{""field2"":1410,""field1"":""This an article summary 3 for this dessert. <em>Short</em> & <em>Sweet!</em>"", ""field7"": ""What's up <strong>Super Joomlers!</strong> <em>Alex</em> here... Proud to be a joomler. Nowadays focusing on Joomla! 4.x Web Services Apis.""}}","{""image_intro"": ""/images/powered_by.png"", ""image_intro_caption"": ""sample intro image caption"", ""image_intro_alt"":""sample intro image alt text"", ""float_intro"":"""",""image_fulltext"": ""/images/powered_by.png"", ""image_fulltext_caption"": ""sample full image caption"", ""image_fulltext_alt"":""sample full image alt text"", ""float_fulltext"":""""}","{""urla"":""https://apiadept.com"",""urlatext"":""Website"",""targeta"":"""",""urlb"":""https://github.com/alexandreelise"",""urlbtext"":""Github"",""targetb"":"""",""urlc"":""https://twitter.com/mralexandrelise"",""urlctext"":""Twitter"",""targetc"":""""}"
EOD,
                ]
            ]
        ]);
    }


    public function testFromStringFromLocalUrl(): never
    {
        $this->markTestIncomplete('Not implemented yet');
    }

    public function testFromStringFromRemoteUrl()
    {
        $value = 'https://example.org/media/com_chococsv/data/sample-data.csv';

        $expected = CsvUrl::class;
        $actual = CsvUrl::fromString($value);
        self::assertInstanceOf($expected, $actual);
    }

    public function testEqualsShouldReturnTrue(): never
    {
        $this->markTestIncomplete('Not implemented yet');
    }

    public function testEqualsShouldReturnFalse(): never
    {
        $this->markTestIncomplete('Not implemented yet');
    }
}
