(function(angular, $, _) {

  // Nb. directive MUST start with lowercase letter.
  angular.module('pelf').directive('pelfContract', ['crmApi', '$timeout', '$q', function(crmApi, $timeout, $q) {
    return {
      // The contract (Activity.getPelfContract) is fed in via attribute.
      scope: {
        contract: '=',
        pelf: '='
      },
      // This directive has its own controller.
      controller: ['$scope', '$location', function ($scope, $location) {
        console.log("contract controller running", $scope);
        $scope.crmUrl = CRM.url;

        if (!$scope.contract) {
          // e.g. user entered wrong URL.
          console.warn("contract controller - NO contract!");
          return;
        }
        // This just simplifies the code below.
        var contract = $scope.contract;

        // Calculated values.
        var updateFundingCalcs = function() { $scope.pelf.fundingCalcs(contract); };
        $scope.$watch('contract.funding', updateFundingCalcs, true);

        $scope.formatDate = CRM.utils.formatDate;

        $scope.editData = false;
        // Edit mode start.
        $scope.editStart = function() {
          $scope.editData = {
            name: contract.subject,
            details: contract.details,
            when: contract.date.substr(0, 10),
          };
          if ($scope.contactWithEditStart) {
            // This is not defined yet in the case of a new contract.
            $scope.contactWithEditStart();
          }
          if ($scope.contactAssignedEditStart) {
            // This is not defined yet in the case of a new contract.
            $scope.contactAssignedEditStart();
          }
        };
        // Save edits.
        $scope.editSave = function() {

          var isNewContract = (contract.id === null);

          var params = {
            subject: $scope.editData.name,
            when: $scope.editData.when,
            details: $scope.editData.details,
            activity_type_id: contract.activity_type_id
          };
          if (!isNewContract) {
            params.id = contract.id;
          }

          // Note to self.  crmApi returns a *promise*, not a function that
          // *returns* a promise.  So you can't chain them like
          // crmApi().then(crmApi()).then(crmApi())...  because then() expects
          // a function (or two) that receive a result parameter.  So you need
          // to wrap it in another function that returns the crmApi() promise
          // result.

          var q = $q.when()
          .then(function() { return crmApi('Activity', 'create', params); })
          .then(function(result) {
            console.log("updating UI after save ",result, params);
            // Update the ID (essential for when we've just created a new contract).
            contract.id = result.id;
            contract.subject = $scope.editData.name;
            contract.details = $scope.editData.details;
            contract.date = $scope.editData.when;
          });

          // Now we know the activity is saved, we can save the targets.
          q.then(function() {
            // This returns a promise.
            return $scope.contactWithEditSave();
          })
          .then(function() {
            // This returns a promise.
            return $scope.contactAssignedEditSave();
          })
          .then(function() {
            console.log("final thing");
            if (isNewContract) {
              // Redirect to proper path for this contract.
              $location.path("/pelf/contracts/" + contract.id);
              $location.replace();
            }
            else {
              // Stop editing.
              $scope.editData = false;
            }
          });
        };
        // Cancel edits.
        $scope.editCancel = function (){
          $scope.contactWithEditCancel();
          $scope.contactAssignedEditCancel();
          $scope.editData = false;
          var isNew = (contract.id === null);
          if (isNew) {
            // Redirect back to prospects list.
            $location.path("/pelf/contracts");
            $location.replace();
          }
        };

        if (!contract.id) {
          // Open new contracts in edit mode.
          $scope.editStart();
        }

      }], // end of controller.
      link: function(scope, elem, attrs) {
        if (true) return;
        if (scope.contract && !scope.contract.id) {
          $timeout(function() {
            elem.find('input[name="contract_name"]').focus();
          });
        }
      },
      templateUrl: '~/pelf/Contract.html',
    };
  }]);

})(angular, CRM.$, CRM._);
