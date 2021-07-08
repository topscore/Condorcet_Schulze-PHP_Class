## public static Election::setMaxVoteNumber

### Description    

```php
public static Election::setMaxVoteNumber ( ?int maxVotesNumber ) : ?int
```

Add a limitation on Election::addVote and related methods. You can't add new vote y the number of registered vote is equall ou superior of this limit.
    

##### **maxVotesNumber:** *?int*   
Null will deactivate this functionality. An integer will fix the limit.    


### Return value:   

*(?int)* *(int or null)* The new limit.


---------------------------------------

### Related method(s)      

* [static Election::setMaxParseIteration](../Election%20Class/public%20static%20Election--setMaxParseIteration.md)    
