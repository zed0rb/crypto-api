<?php

namespace App\Http\Controllers;

use App\Asset;
use App\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AssetsController extends Controller
{
    public function store(Request $request) {
        $this->validate($request, [
            'label' => 'required',
            'currency' => 'required|exists:currencies,ticker',
            'value' => 'required|integer|min:0'
        ]);

        $currencyModel = Currency::where('ticker', $request->input('currency'))->first();

        try {
            Asset::create([
                'label' => $request->input('label'),
                'currency_id' => $currencyModel->id,
                'user_id' => $request->auth->id,
                'value' => $request->input('value')
            ]);
        } catch (\Exception $e) {
            Response::create($e->getMessage(), 500);
        }

        Response::create('Success', 200);
    }

    public function destroy(Request $request) {
        $this->validate($request, [
            'id' => 'required|exists:assets'
        ]);

        try {
            Asset::destroy($request->input('id'));
        } catch (\Exception $e) {
            Response::create($e->getMessage(), 500);
        }

        Response::create('Success', 200);
    }

    public function update(Request $request) {
        $this->validate($request, [
            'id' => 'required|exists:assets',
            'currency' => 'exists:currencies,ticker',
            'value' => 'integer|min:0'
        ]);

        $assetModel = Asset::find($request->input('id'));
        $currencyModel = $request->has('currency')
            ? Currency::where('ticker', $request->input('currency'))->first()
            : $assetModel->currency;

        try {
            $assetModel->label = $request->has('label')
                ? $request->input('label')
                : $assetModel->label;
            $assetModel->currency_id = $currencyModel->id;
            $assetModel->value = $request->has('value')
                ? $request->input('value')
                : $assetModel->value;

            $assetModel->save();
        } catch (\Exception $e) {
            Response::create($e->getMessage(), 500);
        }

        Response::create('Success', 200);
    }

    public function assetsValue(Request $request) {
        $usersAssets = $request->auth->assets->groupBy('currency_id');

        $tickers = Currency::find(
            array_keys($usersAssets->toArray()),
            ['id', 'ticker']
        )->toArray();

        $total = 0;
        $sumedAssetsValueByCurrency = [];
        foreach ($usersAssets as $key => $asset) {
            $ticker = array_values(
                array_filter($tickers, function($t) use ($key){
                    return $t['id'] === $key;
                })
            )[0]['ticker'];

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => 'https://api.cryptonator.com/api/ticker/' . $ticker . '-usd',
                CURLOPT_USERAGENT => 'Codular Sample cURL Request'
            ]);

            $price = json_decode(curl_exec($curl))->ticker->price;

            curl_close($curl);

            $value = $asset->sum('value') * $price;

            $total += $value;
            $sumedAssetsValueByCurrency[$ticker] = round($value, 2);
        }

        return [
            'Assets' => $sumedAssetsValueByCurrency,
            'Total' => round($total, 2)
        ];
    }
}
