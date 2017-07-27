(function(angular, $, _) {

  // Nb. directive MUST start with lowercase letter.
  angular.module('pelf').directive('pelfFunding', function(crmApi) {
    // ? how to tell it that it propsect is needed? where is the prospect thing?
    return {
      // The prospect (Activity.getPelfProspect) is fed in via attribute.
      scope: {
        activityId: '=activityId',
        funding: '=funding'
      },
      // This directive has its own controller.
      controller: ['$scope', function ($scope) {
        console.log("pelfFunding directive", $scope.funding, $scope.activityId, $scope);
        $scope.crmUrl = CRM.url;
        $scope.selected = null;
        $scope.editRow = function(part) {
          if (part === null) {
            part = {};
          }
          console.log('editRow', part);
          // Copy the data into 'selected'.
          $scope.selected = _.assign(
              {
                financial_year: '',
                note: '',
                amount: ''
              },
              part
          );
        };
        var sortAndGroupFunding = function() {
          $scope.groupedFunding = _.map(
            _.groupBy($scope.funding, 'financial_year'),
            function(grp) {
              return {
                sum: _.reduce(grp, function(total, funding) { return (total || 0) + parseFloat(funding.amount); }, 0),
                year: grp[0].financial_year,
                rows: grp
              };
            });
        };
        sortAndGroupFunding();
        $scope.deleteRow = function(part) {
          crmApi('PelfFunding', 'delete', { id: part.id })
            .then(function(result) {
              _.remove($scope.funding, {id: part.id});
              sortAndGroupFunding();
            });
        };
        // Put this on this which gets passed down to child directives?
        this.saveEdits = function(part) {
          var p;
          if (part.id) {
            console.log("save exist");
            // Save existing data.
            p = crmApi('PelfFunding', 'create', _.assign({}, part))
            .then(function(){
              var live = _.find($scope.funding, { id: part.id });
              _.assign(live, part);
              sortAndGroupFunding();
              $scope.selected = null;
            });
          }
          else {
            // New row.
            var row = _.assign({ activity_id: $scope.activityId, sequential: 1}, part);
            console.log("save new", row);
            p = crmApi('PelfFunding', 'create', row)
              // xxx this then function is not being called?
            .then(function(result){
              console.log("result: ", result);
              console.log("funding: ", $scope.funding);
              $scope.funding.push(result.values[0]);
              sortAndGroupFunding();
              $scope.selected = null;
            }, function(e) {console.error(e);});
          }
        };
        this.cancelEdit = function(part) {
          $scope.selected = null;
        };
      }],
      templateUrl: '~/pelf/PelfFunding.html',
    };
  });

  // Re-useable edit form.
  angular.module('pelf').directive('pelfFundingEdit', function() {
    return {
      scope: { editRow: '=editRow'},
      // what does this do transclude: true,
      require: '^^pelfFunding',
      templateUrl: '~/pelf/PelfFundingEdit.html',
      link: function(scope, el, attrs, editor) {
        console.log("link: saveEdits:", editor);
        scope.saveEdits = editor.saveEdits;
        scope.cancelEdit = editor.cancelEdit;
      }
    };
  });

})(angular, CRM.$, CRM._);
