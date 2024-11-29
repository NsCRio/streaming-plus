<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class JellyfinSearchController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    #resources/python/bin/python3 resources/python/run_streaming.py
    #printf '0\n1992\n0\n' | resources/python/bin/python3 resources/python/run_streaming.py

    public function getItems(Request $request){

        $json = '{"Items":[{"Name":"La liberazione di Zoro","ServerId":"cfd006198f034523a91af115cc2cddb9","Id":"ebf426612fd9faef245484ffccadceec","CanDelete":true,"PremiereDate":"1999-11-24T00:00:00.0000000Z","ChannelId":null,"CommunityRating":8.263,"ProductionYear":1999,"IndexNumber":3,"ParentIndexNumber":1,"IsFolder":false,"Type":"Episode","ParentLogoItemId":"2738871dd7a147758c789d4755f965b6","ParentBackdropItemId":"2738871dd7a147758c789d4755f965b6","ParentBackdropImageTags":["5dd7655b7f49d2d358d8e09e4cd539a5"],"UserData":{"PlaybackPositionTicks":0,"PlayCount":1,"IsFavorite":false,"LastPlayedDate":"2024-11-21T21:04:49.571Z","Played":true,"Key":"81797001003","ItemId":"00000000000000000000000000000000"},"SeriesName":"One Piece","SeriesId":"2738871dd7a147758c789d4755f965b6","SeasonId":"8de72cc4d697d1cd538455617721aac3","PrimaryImageAspectRatio":1.7777777777777777,"SeriesPrimaryImageTag":"0cd04fe749c03d19b816f4d81f58f2fc","SeasonName":"Stagione 1","VideoType":"VideoFile","ImageTags":{"Primary":"983f96c01fbaa956fe6157ad91fa420f"},"BackdropImageTags":[],"ParentLogoImageTag":"ef2d24ce0724838096cfc1400c1be3c9","ImageBlurHashes":{"Primary":{"983f96c01fbaa956fe6157ad91fa420f":"WTL;Hi=yEpnMSipJ.A-pXV_1kpo#tlT1IV--xoM|%#Sixany-mt8","0cd04fe749c03d19b816f4d81f58f2fc":"dLI;u3-pEM~WIorXIBIV0hi^.8oMQ-ng%fMx-mnMt7xG"},"Logo":{"ef2d24ce0724838096cfc1400c1be3c9":"OUE:A5-;%L#\u002BaJElsD?woLaejYaebIo1?uRQV[S$oz$#ox"},"Thumb":{"ccf7d8cd4dc2f157d0dd42f2e59ee8ee":"NGKv~hqDImxBO??u^R%g-V$_vzkYBW$,-oEl$m%g"},"Backdrop":{"5dd7655b7f49d2d358d8e09e4cd539a5":"WVIEhSyDs.w^t39t_MsFVst7t5WF.QxbW8V{tPfk-nWFWUE3o{x]"}},"ParentThumbItemId":"2738871dd7a147758c789d4755f965b6","ParentThumbImageTag":"ccf7d8cd4dc2f157d0dd42f2e59ee8ee","LocationType":"FileSystem","MediaType":"Video"}],"TotalRecordCount":1,"StartIndex":0}';
        $json = '{"Items":[{"Name":"One Piece","ServerId":"cfd006198f034523a91af115cc2cddb9","Id":"2738871dd7a147758c789d4755f965b6","PremiereDate":"1998-07-26T00:00:00.0000000Z","OfficialRating":"TV-14","ChannelId":null,"CommunityRating":8.5,"RunTimeTicks":15000000000,"ProductionYear":1998,"IsFolder":true,"Type":"Series","UserData":{"UnplayedItemCount":941,"PlaybackPositionTicks":0,"PlayCount":0,"IsFavorite":false,"Played":false,"Key":"81797","ItemId":"00000000000000000000000000000000"},"Status":"Continuing","AirDays":[],"ImageBlurHashes":{},"LocationType":"FileSystem","MediaType":"Unknown","EndDate":"2024-10-13T00:00:00.0000000Z"},{"Name":"1992","ServerId":"cfd006198f034523a91af115cc2cddb9","Id":"e26770fa7320cf02f6bc1436f7ce1d63","Container":"hls","PremiereDate":"2024-08-29T00:00:00.0000000Z","OfficialRating":"R","ChannelId":null,"CommunityRating":6.6,"RunTimeTicks":58319166660,"ProductionYear":2024,"IsFolder":false,"Type":"Movie","UserData":{"PlaybackPositionTicks":0,"PlayCount":9,"IsFavorite":false,"LastPlayedDate":"2024-11-28T18:01:52.4030928Z","Played":false,"Key":"e26770fa-7320-cf02-f6bc-1436f7ce1d63","ItemId":"00000000000000000000000000000000"},"VideoType":"VideoFile","ImageBlurHashes":{},"LocationType":"FileSystem","MediaType":"Video"}],"TotalRecordCount":2,"StartIndex":0}';

        return response($json)->header('Content-Type', 'application/json');
    }
}
