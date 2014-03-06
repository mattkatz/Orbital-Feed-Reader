angular.module('autocomplete-directive',[])
.directive('mkAutocomplete', function(){
  return {
    restrict: 'EA',
    replace: true,
    scope: {
      ngModel: "=",
      suggestionSource: "=",
    },
    replace: "true",//this breaks my find an input test
    template:
      '<div class="autocomplete"><input placeholder="begin typing to get suggestions" data-ng-change="processChange()" data-ng-keypress="processKey($event)" data-ng-model="ngModel" autocomplete="off"/> '+
      '<ul  class="suggestions" ng-show="suggestions">'+
        '<li class="suggestion" data-ng-repeat="suggestion in suggestions" data-ng-class="suggestion == currentResult ? selectClass: null">{{suggestion}}</li>' +
      '</uL></div>',

    link: function(scope,element, attrs, controller) {
      scope.minLength = attrs.minLength || 2;
      scope.multiple = attrs.multiple || true;
      scope.multipleSeparator = attrs.multipleSeparator ||", ";
      scope.selectClass = attrs.selectClass ||'selected' ;
      scope.processChange = function(){
        scope.suggestions = [];
        //var q = scope.inputValue.trim();
        var q = scope.ngModel.trim();
        if(scope.multiple){
          sep = scope.multipleSeparator;
          //Is there anything after our sep
          //If there isn't, we should check a trimmed version
          //set q to be the first thing that isn't falsy. 
          q = scope.getQueryEnd(q,sep)|| scope.getQueryEnd(q,sep.trim())|| q;
        }
        if(q.length >= scope.minLength){
          scope.suggestions = scope.getSuggestions(q,scope.suggestionSource);
        }
      };
      //returns last thing after a sep
      scope.getQueryEnd = function(q,sep){
        sepPos = q.lastIndexOf(sep);
        if(-1 == sepPos)return false;//exit quickly
        return q.substr(sepPos + sep.length).trim();
      }
      scope.getQueryBegin = function(q, sep){
        sepPos = q.lastIndexOf(sep);
        if(-1 == sepPos)return false;//exit quickly
        return q.substr(0,sepPos + sep.length).trim();
      }
      scope.currentResult = null;
      scope.suggestions = [];
      scope.$watch("value", function(newValue,oldValue){
        //console.log(newValue);
        //search the availabletags for possible matches
        //console.log(scope.suggestions);
      })
      scope.getCurrentResult = function(){
        var currentResult;
        if(  scope.suggestions.length <= 0){return false;}
        return scope.currentResult;
      };
      scope.selectCurrentResult = function(){
        scope.currentResult = scope.getCurrentResult();
        if(scope.currentResult){
          if(scope.multiple){
            text = scope.ngModel.trim();
            sep = scope.multipleSeparator;
            currentVal = scope.getQueryBegin(text,sep) || scope.getQueryBegin(text, sep.trim())||"";
            scope.ngModel = currentVal + scope.currentResult + scope.multipleSeparator;
            //TODO focus the input box
          } else {
            scope.ngModel = scope.currentResult;
          }
          scope.suggestions = [];
          scope.currentResult = null;
          //TODO fire an onselsect event
        }
      };
      scope.nextResult = function(){
        if(scope.currentResult){
          index =  scope.suggestions.indexOf(scope.currentResult);
          if(index++ < scope.suggestions.length ){
            scope.currentResult = scope.suggestions[index];
          }
        }
        else if(scope.suggestions.length >0){
          scope.currentResult = scope.suggestions[0];
        }
      };
      scope.prevResult = function(){
        if(scope.currentResult){
          index =  scope.suggestions.indexOf(scope.currentResult);
          if(index-- >=0){
            scope.currentResult = scope.suggestions[index];
          }
        }
        else if(scope.suggestions.length > 0){
          scope.currentResult =scope.suggestions[scope.suggestions.length -1];
        }
      };
      scope.processKey = function(event){
        var enter = 13, tab = 9, esc = 27, up = 38, down = 40, left = 37, right = 39;
        //cancel other handlers if we've got it
        //if up/down/escape and we have results
        //or if enter/tab and we have results and one has been selected
        if((scope.suggestions.length >0) && ([enter,tab,esc,up,down].indexOf(event.keyCode) != -1) &&
          (([enter,tab].indexOf(event.keyCode) ==-1)|| scope.currentResult)){
            if(event.stopImmediatePropagation) event.stopImmediatePropagation();
            if(event.preventDefault) event.preventDefault();
            if(event.stopPropagation) event.stopPropagation();
            if(event.cancelBubble) event.cancelBubble = true;
        }else{
          scope.currentResult = null;
          scope.suggestions = [];
          return;
        }
        switch(event.keyCode){
          case up:
            scope.prevResult();
            break;
          case down:
            scope.nextResult();
            break;
          case tab:
          case enter:
            event.stopImmediatePropagation();
            scope.selectCurrentResult();
            break;
          case esc:
            event.stopImmediatePropagation();
            //hide the results or empty them
            scope.suggestions = [];
            sccope.currentResult = null;
            break;
        }
      };
      scope.getSuggestions = function(filterVal, candidates){
        winners = [];
        candidates.forEach(function(candidate){
          if(candidate.contains(filterVal)){
            winners.push(candidate);
          }
        });
        return winners;
      };
    },
  }
});
