> **[Presentation](README.md) | [Manual](https://github.com/julien-boudry/Condorcet/wiki) | [Methods References](Documentation/README.md) | [Tests](Tests/)**  

Condorcet PHP: Voting Methods
===========================

> _See also **[the manual](https://github.com/julien-boudry/Condorcet/wiki/I-%23-Installation---Basic-Configuration-%23-2.-Voting-Methods)**_  

# Natively implemented methods 
*The modular architecture allows you to import new methods as external classes. These are preloaded into the distribution.*  

* **Condorcet Basic** Give you the natural winner or loser of Condorcet if there is one.  
* **Borda count**
    * **[Borda System](#borda-count)**
    * **[Dowdall system (Nauru)](#dowdall-system-nauru)**
* **[Copeland](#copeland)**
* **Dodgson Approximations**
    * **[Dodgson Quick](#dodgson-quick)**
    * **[Dodgson Tideman approximation](#dodgson-tideman-approximation)**
* **[Instant-runoff](#instant-runoff-alternative-vote)** *(Alternative Vote / Preferential Voting)*
* **[Kemeny–Young](#kemenyyoung)**
* **Majority Family**
    * **[First-past-the-post](#first-past-the-post)**
    * **[Multiple Rounds system](#multiple-rounds-system)**
* **Minimax Family**
    * **[Minimax Winning](#minimax-winning)**
    * **[Minimax Margin](#minimax-margin)**
    * **[Minimax Opposition](#minimax-opposition)**
* **Ranked Pairs Family** *(Tideman method)*
    * **[Ranked Pairs Margin](#ranked-pairs-margin)**
    * **[Ranked Pairs Winning](#ranked-pairs-winning)**
* **Schulze Method**
    * **[Schulze Winning](#schulze-winning)** *(recommended)*
    * **[Schulze Margin](#schulze-margin)**
    * **[Schulze Ratio](#schulze-ratio)**
* **[Single Transferable Vote](#single-transferable-vote)** *(STV)*


# Methods Details & Implementation

## Implementation Philophy

#### Result tie-breaking
Unless explicitly stated otherwise in the details below, no tie-breaking is added to methods, we kept them pure.  
The results are therefore likely to contain ties in some ranks. Which according to the algorithms is more or less frequent, but always tends to become less likely in proportion to the size of the election. 

#### Tie into a vote rank
Unless you have prohibited ties yourself or via a filter (CondorcetPHP >= 1.8), the votes are therefore likely to contain ties on certain ranks. In principle, this does not particularly disturb Condorcet's methods, since they are based on the Pairwise.  
This is more annoying for other methods like Borda, Instant-runoff or Ftpt. These methods being based on the rank assigned. How each handles these cases is specified below. Keep in mind that it can vary depending on the implementations. Some choices had to be made for each of them.

#### Implicit vs Explicit Ranking
Please read the manual [about explicit and implicit ranking](https://github.com/julien-boudry/Condorcet/wiki/II-%23-C.-Result-%23-3.-Ranking-mode---Implicit-versus-Partial) modes.  
In terms of implementation, what you have to understand is that algorithms and pairwise are blind. And see votes in their implicit or explicit context, which can significantly change the results of some of them.  


## Condorcet Basic

> **Family:** Condorcet  
> **Variant used:** *None*  
> **Wikipedia:** https://en.wikipedia.org/wiki/Condorcet_method   

### Implementation Comments
*None*

```php
// Will return the strict natural Condorcet Winner candidate. Or Null if there is not.
$election->getCondorcetWinner() ; 
// Will return the strict natural Condorcet Loser candidate. Or Null if there is not.
$election->getCondorcetLoser() ;
```


## Borda Count

> **Family:** Borda Count  
> **Variant used:** *Starting at 1*  
> **Wikipedia:** https://en.wikipedia.org/wiki/Borda_count  
> ***  
> **Methods alias available (for function call)**: "BordaCount","Borda Count","Borda","Méthode Borda"  

### Implementation Comments
By default the option is to start the count at n - 1. You can change it with BordaCount::setOption(), see below.  

In case of tie into a vote rank, follow this example:  
```
A>B=C=D=E>F  
A: 6 points  
B/C/D/E: (5+4+3+2) / 4 = 3.5 points each  
F: 1 point
```

In case of explicit voting is disabled. Missing rank does not earn points, but the existing rank are not penalized.

### Code example
```php
// Get Full Ranking
$election->getResult('BordaCount') ;

// Just get Winner or Loser
$election->getWinner('BordaCount') ;
$election->getLoser('BordaCount') ;

// Get Stats
$election->getResult('BordaCount')->getStats() ;

// Chante the staring point to n - 0
$election->setMethodOption('BordaCount', 'Starting', 0) ;
$election->getResult('BordaCount') ;
```


## Dowdall system (Nauru)

> **Family:** Borda Count  
> **Variant used:** *Dowdall System*  
> **Wikipedia:** https://en.wikipedia.org/wiki/Borda_count  
> ***  
> **Methods alias available (for function call)**: "DowdallSystem","Dowdall System","Nauru", "Borda Nauru"  

### Implementation Comments  
 *See comments on the original Borda method above.*  

### Code example
```php
// Get Full Ranking
$election->getResult('DowdallSystem') ;

// Just get Winner or Loser
$election->getWinner('DowdallSystem') ;
$election->getLoser('DowdallSystem') ;

// Get Stats
$election->getResult('DowdallSystem')->getStats() ;
```


## Copeland

> **Family:** Copeland method  
> **Variant used:** *None*  
> **Wikipedia:** http://en.wikipedia.org/wiki/Copeland%27s_method  
> ***  
> **Methods alias available (for function call)**: "Copeland"  

### Implementation Comments  
 *None*

### Code example

```php
// Get Full Ranking
$election->getResult('Copeland') ;

// Just get Winner or Loser
$election->getWinner('Copeland') ;
$election->getLoser('Copeland') ;

// Get Stats
$election->getResult('Copeland')->getStats() ;
```


## Dodgson Quick

> **Family:** Dodgson method  
> **Variant used:** Approximation for Dodgson method called "Dodgson Quick" from https://www.maa.org/sites/default/files/pdf/cmj_ftp/CMJ/September%202010/3%20Articles/6%2009-229%20Ratliff/Dodgson_CMJ_Final.pdf  
> **Wikipedia:** https://en.wikipedia.org/wiki/Dodgson%27s_method  
> ***  
> **Methods alias available (for function call)**: "Dodgson Quick" / "DodgsonQuick" / "Dodgson Quick Winner"  

### Implementation Comments  
 *None*  

### Code example
```php
// Get Full Ranking
$election->getResult('Dodgson Quick') ;

// Just get Winner or Loser
$election->getWinner('Dodgson Quick') ;
$election->getLoser('Dodgson Quick') ;

// Get Stats
$election->getResult('Dodgson Quick')->getStats() ;
```


## Dodgson Tideman Approximation

> **Family:** Dodgson method  
> **Variant used:** Approximation for Dodgson method called "Tideman approximation" from _[Lewis  Carroll,  voting,  and  the  taxicab  metric](https://www.maa.org/sites/default/files/pdf/cmj_ftp/CMJ/September%202010/3%20Articles/6%2009-229%20Ratliff/Dodgson_CMJ_Final.pdf)_  
> **Wikipedia:** https://en.wikipedia.org/wiki/Dodgson%27s_method  
> ***  
> **Methods alias available (for function call)**: "Dodgson Tideman Approximation" / "DodgsonTidemanApproximation" / "Dodgson Tideman" / "DodgsonTideman"  

### Implementation Comments  
 *None*  

### Code example
```php
// Get Full Ranking
$election->getResult('Dodgson Tideman') ;

// Just get Winner or Loser
$election->getWinner('Dodgson Tideman') ;
$election->getLoser('Dodgson Tideman') ;

// Get Stats
$election->getResult('Dodgson Tideman')->getStats() ;
```


## Instant-runoff (Alternative Vote)

> **Family:** Instant-runoff  
> **Variant used:** *None*  
> **Wikipedia:** https://en.wikipedia.org/wiki/Instant-runoff_voting  
> ***  
> **Methods alias available (for function call)**: "Instant-runoff", "InstantRunoff", "preferential voting", "ranked-choice voting", "alternative vote", "AlternativeVote", "transferable vote", "Vote alternatif"  

### Implementation Comments  
In case of tie into a vote rank, rank is ignored like he never existed.  

An additional tie-breaking tentative is added in case of tie into the preliminary result set. First, comparing candidate pairwise, in a second attempt compare the total number of pairwise wins (global context), and in a third desperate attempt, compare the balance of their victory/defeat in a global Pairwise context.

### Code example

```php
// Get Full Ranking
$election->getResult('Instant-runoff') ;

// Just get Winner or Loser
$election->getWinner('Instant-runoff') ;
$election->getLoser('Instant-runoff') ;

// Get Stats
$election->getResult('Instant-runoff')->getStats() ;
```


## Kemeny–Young

> **Family:** Kemeny–Young method  
> **Variant used:** *None*  
> **Wikipedia:** http://en.wikipedia.org/wiki/Kemeny-Young_method _Kemeny-Young  
> ***  
> **Methods alias available (for function call)**: "Kemeny–Young" / "Kemeny-Young" / "Kemeny Young" / "KemenyYoung" / "Kemeny rule" / "VoteFair popularity ranking" / "Maximum Likelihood Method" / "Median Relation"  

### Implementation Comments  
Kemeny-Young is currently limited to up 8 candidates. Note that, for 8 candidates, you must provide into php.ini a memory_limit upper than 160MB.  

### Code example
```php
// Get Full Ranking
$election->getResult('Kemeny-Young') ;

// Just get Winner or Loser
$election->getWinner('Kemeny-Young'') ;
$election->getLoser('Kemeny-Young') ;

// Get Stats
$election->getResult('Kemeny-Young')->getStats() ;
```


## First-past-the-post

> **Family:** Majority  
> **Variant used:** *See implementation comment*  
> **Wikipedia:** https://en.wikipedia.org/wiki/First-past-the-post_voting  
> ***  
> **Methods alias available (for function call)**: "First-past-the-post voting", "First-past-the-post", "First Choice", "FirstChoice", "FPTP", "FPP", "SMP"

### Implementation Comments  
In case of tie into the first rank. All non-commissioned candidates earn points, but only a fraction. But not 1 point, the result of this computation: 1/(candidate-in-rank).  

For example: ```A = B > C```
A/B earn each 0.5 points

### Code example
```php
// Get Full Ranking
$election->getResult('FPTP') ;

// Just get Winner or Loser
$election->getWinner('FPTP') ;
$election->getLoser('FPTP') ;

// Get Stats
$election->getResult('FPTP')->getStats() ;
```


## Multiple Rounds system

> **Family:** Majority  
> **Variant used:** *See implementation comment*  
> **Wikipedia:** https://en.wikipedia.org/wiki/Two-round_system
> ***  
> **Methods alias available (for function call)**: "Multiple Rounds System", "MultipleRoundsSystem", "Multiple Rounds", "Majority", "Majority System", "Two-round system", "second ballot", "runoff voting", "ballotage", "two round system", "two round", "two rounds", "two rounds system", "runoff voting"

### Implementation Comments  
In case of tie into the first rank. All non-commissioned candidates earn points, but only a fraction. But not 1 point, the result of this computation: 1/(candidate-in-rank).  
For example: ```A = B > C```  
A/B earn each 0.5 points  
 
Method is trying to keep only two candidates for the next round. But that may be more in the event of a perfect tie.  

### Code example
```php
// Get Full Ranking
$election->getResult('Multiple Rounds System') ;

// Just get Winner or Loser
$election->getWinner('Multiple Rounds System') ;
$election->getLoser('Multiple Rounds System') ;

// Get Stats
$election->getResult('Multiple Rounds System')->getStats() ;
```


## Minimax Winning

> **Family:** Minimax method  
> **Variant used:** Winning *(Does not satisfy the Condorcet loser criterion)*  
> **Wikipedia:** https://en.wikipedia.org/wiki/Minimax_Condorcet  
> ***  
> **Methods alias available (for function call)**: "Minimax Winning" / "MinimaxWinning" / "Minimax" / "Minimax_Winning" / "Simpson" / "Simpson-Kramer" / "Simpson-Kramer Method" / "Simpson Method"  

### Implementation Comments  
 *None*  

### Code example
```php
// Get Full Ranking
$election->getResult('Minimax Winning') ;

// Just get Winner or Loser
$election->getWinner('Minimax Winning') ;
$election->getLoser('Minimax Winning') ;

// Get Stats
$election->getResult('Minimax Winning')->getStats() ;
```


## Minimax Margin

> **Family:** Minimax method  
> **Variant used:** Margin *(Does not satisfy the Condorcet loser criterion)*  
> **Wikipedia:** https://en.wikipedia.org/wiki/Minimax_Condorcet  
> ***  
> **Methods alias available (for function call)**: "Minimax Margin" / "MinimaxMargin" / "MinimaxMargin" / "Minimax_Margin"  

### Implementation Comments  
 *None*  

### Code example
```php
// Get Full Ranking
$election->getResult('Minimax Margin') ;

// Just get Winner or Loser
$election->getWinner('Minimax Margin') ;
$election->getLoser('Minimax Margin') ;

// Get Stats
$election->getResult('Minimax Margin')->getStats() ;
```


## Minimax Opposition

> **Family:** Minimax method  
> **Variant used:** Opposition *(By nature, this alternative does not meet any criterion of Condorcet)*  
> **Wikipedia:** https://en.wikipedia.org/wiki/Minimax_Condorcet  
> ***  
> **Methods alias available (for function call)**: "Minimax Opposition" / "MinimaxOpposition" / "Minimax_Opposition"  

### Implementation Comments  
 *None*  

### Code example
```php
// Get Full Ranking
$election->getResult('Minimax Opposition') ;

// Just get Winner or Loser
$election->getWinner('Minimax Opposition') ;
$election->getLoser('Minimax Opposition') ;

// Get Stats
$election->getResult('Minimax Opposition')->getStats() ;
```


## Ranked Pairs Margin

> **Family:** Ranked Pairs  
> **Variant used:** Margin *(Ranked Pairs Margin is used by Nicolaus Tideman himself from originals papers. But it's not necessarily the most common. Most other documentation preferring the Winning variant. Even Wikipedia is the different from one language to another.)*  
**Wikipedia:** https://en.wikipedia.org/wiki/Ranked_pairs  
> ***  
> **Methods alias available (for function call)**: "Ranked Pairs Margin" / "Tideman Margin" / "RP Margin" / "Ranked Pairs" / "RankedPairs" / "Tideman method"  

### Implementation Comments  
In the event of the impossibility of ordering a pair by their margin of victory. Try to separate them when possible by their smaller minority opposition.  
In case of a tie in the ranking result. No advanced methods are used. It is, therefore, an implementation following the first paper published in 1987. Markus Schulze advice a tie-breaking method, but it brings unnecessary complexity and is partly based on randomness. this method can, therefore, come out ties on some ranks. Even if that is very unlikely on an honest election of good size.  

### Code example
```php
// Get Full Ranking
$election->getResult('Ranked Pairs Margin') ;

// Just get Winner or Loser
$election->getWinner('Ranked Pairs Margin') ;
$election->getLoser('Ranked Pairs Margin') ;

// Get Stats
$election->getResult('Ranked Pairs Margin')->getStats() ;
```


## Ranked Pairs Winning

> **Family:** Ranked Pairs  
> **Variant used:** Winning  
> **Wikipedia:** https://en.wikipedia.org/wiki/Ranked_pairs  
> ***  
> **Methods alias available (for function call)**: "Ranked Pairs Winning" / "Tideman Winning" / "RP Winning"  

### Implementation Comments  
In the event of the impossibility of ordering a pair by their margin of victory. Try to separate them when possible by their smaller minority opposition.  
In case of a tie in the ranking result. No advanced methods are used. It is, therefore, an implementation following the first paper published in 1987. Markus Schulze advice a tie-breaking method, but it brings unnecessary complexity and is partly based on randomness. this method can, therefore, come out ties on some ranks. Even if that is very unlikely on an honest election of good size.  

### Code example
```php
// Get Full Ranking
$election->getResult('Ranked Pairs Winning') ;

// Just get Winner or Loser
$election->getWinner('Ranked Pairs Winning') ;
$election->getLoser('Ranked Pairs Winning') ;

// Get Stats
$election->getResult('Ranked Pairs Winning')->getStats() ;
```


## Schulze Winning

> **Family:** Schulze method  
> **Variant used:** Winning *(Schulze Winning is recommended by Markus Schulze himself. This is the default choice. This variant is also known as Schulze Method.)*  
> **Wikipedia:** https://en.wikipedia.org/wiki/Schulze_method  
> ***  
> **Methods alias available (for function call)**: "Schulze Winning" / "Schulze" / "SchulzeWinning" / "Schulze_Winning" / "Schwartz Sequential Dropping" / "SSD" / "Cloneproof Schwartz Sequential Dropping" / "CSSD" / "Beatpath" / "Beatpath Method" / "Beatpath Winner" / "Path Voting" / "Path Winner"  

### Implementation Comments  
 *None*  

### Code example
```php
// Get Full Ranking
$election->getResult('Schulze') ;

// Just get Winner or Loser
$election->getWinner('Schulze') ;
$election->getLoser('Schulze') ;

// Get Stats
$election->getResult('Schulze')->getStats() ;
```


## Schulze Margin

> **Family:** Schulze method  
> **Variant used:** Margin    
> **Wikipedia:** https://en.wikipedia.org/wiki/Schulze_method  
> ***  
> **Methods alias available (for function call)**: "Schulze Margin" / "SchulzeMargin" / "Schulze_Margin"  

### Implementation Comments  
 *None*  

### Code example
```php
// Get Full Ranking
$election->getResult('Schulze Margin') ;

// Just get Winner or Loser
$election->getWinner('Schulze Margin') ;
$election->getLoser('Schulze Margin') ;

// Get Stats
$election->getResult('Schulze Margin')->getStats() ;
```


## Schulze Ratio

> **Family:** Schulze method  
> **Variant used:** Ratio    
> **Wikipedia:** https://en.wikipedia.org/wiki/Schulze_method  
> ***  
> **Methods alias available (for function call)**: "Schulze Ratio" / "SchulzeRatio" / "Schulze_Ratio"  

### Implementation Comments  
The original specification is incomplete. She says to compute the ratio as follow:  
```$candidateA_versus_CandidateB['pairwise_win'] / $candidateA_versus_CandidateB ['pairwise_lose'] = Ratio```  
We don't know how to manage division by zero when it's happened, which is very unlikely on large elections but can happen. Actually, but it can change to a better solution, we add 1 on left and right, only in this case.  

### Code example
```php
// Get Full Ranking
$election->getResult('Schulze Ratio') ;

// Just get Winner or Loser
$election->getWinner('Schulze Ratio') ;
$election->getLoser('Schulze Ratio') ;

// Get Stats
$election->getResult('Schulze Ratio')->getStats() ;
```


## Single Transferable Vote

> **Family:** Single Transferable Vote  
> **Variant used:** *None*  
> **Wikipedia:** https://en.wikipedia.org/wiki/Single_transferable_vote  
> ***  
> **Methods alias available (for function call)**: "STV", "Single Transferable Vote", "SingleTransferableVote"  

### Implementation Comments  
In case of tie into a vote rank, rank is ignored like he never existed.  
The implementation of this method does not support parties. A candidate is elected only once, whatever the number of seats.  
Non-elected candidates are not included in the ranking. The ranking is therefore that of the elected.  

### Code example

```php
// Change the number of seats
$election->setNumberOfSeats(42); # Default is 100

// Get the elected candidates with ranking
$election->getResult('STV');

// Check the number of seats
$election->getResult('STV')->getNumberOfSeats();

// Get Stats (votes needed to win, rounds detailsd)
$election->getResult('STV')->getStats();
```