<?php
require_once 'jsonRPCClient.php';

$sumcoin = new jsonRPCClient('http://user:password@127.0.0.1:3332/');

try {
	$info = $sumcoin->getblockchaininfo();
} catch (Exception $e) {
	echo nl2br($e->getMessage()).'<br />'."\n"; 
	die();
}

// Sumcoin settings
$blockStartingReward = 50;
$blockHalvingSubsidy = 210000;
$blockTargetSpacing = 10;
$maxCoins = 21000000;

$blocks = $info['blocks'];
$coins = CalculateTotalCoins($blockStartingReward, $blocks, $blockHalvingSubsidy);
$blocksRemaining = CalculateRemainingBlocks($blocks, $blockHalvingSubsidy);

$avgBlockTime = GetFileContents("timebetweenblocks.txt");
if (empty($avgBlockTime)) {
	$avgBlockTime = $blockTargetSpacing;
}

$blocksPerDay = (60 / $avgBlockTime) * 24;
$blockHalvingEstimation = $blocksRemaining / $blocksPerDay * 24 * 60 * 60;
$blockString = '+' . (int)$blockHalvingEstimation . ' second';
$blockReward = CalculateRewardPerBlock($blockStartingReward, $blocks, $blockHalvingSubsidy);
$coinsRemaining = $blocksRemaining * $blockReward;
$nextHalvingHeight = $blocks + $blocksRemaining;
$inflationRate = CalculateInflationRate($coins, $blockReward, $blocksPerDay);
$inflationRateNextHalving = CalculateInflationRate(CalculateTotalCoins($blockStartingReward, $nextHalvingHeight, $blockHalvingSubsidy), 
	CalculateRewardPerBlock($blockStartingReward, $nextHalvingHeight, $blockHalvingSubsidy), $blocksPerDay);
$price = GetFileContents("price.txt");

function GetHalvings($blocks, $subsidy) {
	return (int)($blocks / $subsidy);
}

function CalculateRemainingBlocks($blocks, $subsidy) {
	$halvings = GetHalvings($blocks, $subsidy);
	if ($halvings == 0) {
		return $subsidy - $blocks;
	} else {
		$halvings += 1;
		return $halvings * $subsidy - $blocks;
	}
}

function CalculateRewardPerBlock($blockReward, $blocks, $subsidy) {
	$halvings = GetHalvings($blocks, $subsidy);
	$blockReward >>= $halvings;
	return $blockReward;
}

function CalculateTotalCoins($blockReward, $blocks, $subsidy) {
	$halvings = GetHalvings($blocks, $subsidy);
	if ($halvings == 0) {
		return $blocks * $blockReward;
	} else {
		$coins = 0;
		for ($i = 0; $i < $halvings; $i++) {
			$coins += $blockReward * $subsidy;
			$blocks -= $subsidy;
			$blockReward = $blockReward / 2; 
		}
		$coins += $blockReward * $blocks;
		return $coins;
	}
}

function CalculateInflationRate($totalCoins, $blockReward, $blocksPerDay) {
	return pow((($totalCoins + $blockReward) / $totalCoins), (365 * $blocksPerDay)) - 1;
}

function GetFileContents($filename) {
	$file = fopen($filename, "r") or die("Unable to open file!");
	$result = fread($file,filesize($filename));
	fclose($file);
	return $result;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Sumcoin Block Reward Halving Countdown website">
	<meta name="author" content="">
	<link rel="shortcut icon" href="favicon.png">
	<title>Sumcoin Block Reward Halving Countdown</title>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
	<link rel="stylesheet" href="css/flipclock.css">
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
	<script src="js/flipclock.js"></script>	
</head>
<body>
	<div class="container">
		<div class="page-header" style="text-align:center">
			<h3>Sumcoin Block Reward Halving Countdown</h3>
		</div>
		<div class="flip-counter clock" style="display: flex; align-items: center; justify-content: center;"></div>
		<script type="text/javascript">
			var clock;

			$(document).ready(function() {
				clock = $('.clock').FlipClock(<?=$blockHalvingEstimation?>, {
					clockFace: 'DailyCounter',
					countdown: true
				});
			});
		</script>
		<div style="text-align:center">
			Reward-Drop ETA date: <strong><?=date('d M Y H:i:s', strtotime($blockString, time()))?></strong><br/><br/>
			<p>The Sumcoin block mining reward halves every <?=number_format($blockHalvingSubsidy)?> blocks, the coin reward will decrease from <?=$blockReward?> to <?=$blockReward / 2 ?> coins. 
			<br/><br/>
		</div>
		<table class="table table-striped">
			<tr><td><b>Total Sumcoins in circulation:</b></td><td align = "right"><?=number_format($coins)?></td></tr>
			<tr><td><b>Total Sumcoins to ever be produced:</b></td><td align = "right"><?=number_format($maxCoins)?></td></tr>
			<tr><td><b>Percentage of total Sumcoins mined:</b></td><td align = "right"><?=number_format($coins / $maxCoins * 100 / 1, 4)?>%</td></tr>
			<tr><td><b>Total Sumcoins left to mine:</b></td><td align = "right"><?=number_format($maxCoins - $coins)?></td></tr>
			<tr><td><b>Total Sumcoins left to mine until next blockhalf:</b></td><td align = "right"><?= number_format($coinsRemaining);?></td></tr>
			<tr><td><b>Sumcoin price (USD):</b></td><td align = "right">$<?=number_format($price, 2);?></td></tr>
			<tr><td><b>Market capitalization (USD):</b></td><td align = "right">$<?=number_format($coins * $price, 2);?></td></tr>
			<tr><td><b>Sumcoins generated per day:</b></td><td align = "right"><?=number_format($blocksPerDay * $blockReward);?></td></tr>	
			<tr><td><b>Sumcoin inflation rate per annum:</b></td><td align = "right"><?=number_format($inflationRate * 100 / 1, 2);?>%</td></tr>
			<tr><td><b>Sumcoin inflation rate per annum at next block halving event:</b></td><td align = "right"><?=number_format($inflationRateNextHalving * 100 / 1, 2);?>%</td></tr>
			<tr><td><b>Sumcoin inflation per day (USD):</b></td><td align = "right">$<?=number_format($blocksPerDay * $blockReward * $price);?></td></tr>
			<tr><td><b>Sumcoin inflation until next blockhalf event based on current price (USD):</b></td><td align = "right">$<?=number_format($coinsRemaining * $price);?></td></tr>
			<tr><td><b>Total blocks:</b></td><td align = "right"><?=number_format($blocks);?></td></tr>
			<tr><td><b>Blocks until mining reward is halved:</b></td><td align = "right"><?=number_format($blocksRemaining);?></td></tr>
			<tr><td><b>Approximate block generation time:</b></td><td align = "right"><?=number_format($avgBlockTime, 2);?> minutes</td></tr>
			<tr><td><b>Approximate blocks generated per day:</b></td><td align = "right"><?=$blocksPerDay;?></td></tr>
			<tr><td><b>Difficulty:</b></td><td align = "right"><?=number_format($info['difficulty']);?></td></tr>
			<tr><td><b>Hash rate:</b></td><td align = "right"><?=number_format($sumcoin->getnetworkhashps() / 1000 / 1000 / 1000 / 1000 / 1000 / 1000, 2) . ' Exahashes/s';?></td></tr>
		</table>
		<div style="text-align:center">
			<img src="../images/sumcoin.png" width="100px"; height="100px">
			<br/>
			<h2><a href="http://www.litecoinblockhalf.com">Litecoin Block Halving Countdown</a></h2>
		</div>
	</div>
</body>
</html>
