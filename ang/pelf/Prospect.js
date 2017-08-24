(function(angular, $, _) {

  // Nb. directive MUST start with lowercase letter.
  angular.module('pelf').directive('pelfProspect', ['crmApi', '$timeout', '$q', function(crmApi, $timeout, $q) {
    return {
      // The prospect (Activity.getPelfProspect) is fed in via attribute.
      scope: {
        prospect: '=',
        pelf: '='
      },
      // This directive has its own controller.
      controller: ['$scope', '$location', function ($scope, $location ) {
        console.log("prospect controller running", $scope, $scope.pelf);
        $scope.crmUrl = CRM.url;

        if (!$scope.prospect) {
          // e.g. user entered wrong URL.
          console.warn("prospect controller - NO PROSPECT!");
          return;
        }
        // This just simplifies the code below.
        var prospect = $scope.prospect;

        // Ensure numbers are floats.
        prospect.scale = parseFloat(prospect.scale);
        $scope.estWorth = function() { return Math.round($scope.sumFunding()  * prospect.scale / 100, 0); };
        $scope.sumFunding = function() {
          return Math.round(_.reduce(prospect.funding, function(tot, row) { return tot+parseFloat(row.amount); }, 0) ,0);
        };
        $scope.formatDate = CRM.utils.formatDate;

        $scope.editData = false;
        // Edit mode start.
        $scope.editStart = function() {
          console.log("contactListEditSave4", $scope.contactListEditSave);
          $scope.editData = {
            name: prospect.subject,
            scale: prospect.scale,
            stage: prospect.stage,
            details: prospect.details,
            when: prospect.date.substr(0, 10),
          };
          if ($scope.contactWithEditStart) {
            // This is not defined yet in the case of a new prospect.
            $scope.contactWithEditStart();
          }
          if ($scope.contactAssignedEditStart) {
            // This is not defined yet in the case of a new prospect.
            $scope.contactAssignedEditStart();
          }
        };
        // Save edits.
        $scope.editSave = function() {

          var isNewProspect = (prospect.id === null);
          console.log("contactListEditSave1", $scope.contactListEditSave);

          var params = {
            subject: $scope.editData.name,
            when: $scope.editData.when,
            details: $scope.editData.details,
            activity_type_id: prospect.activity_type_id
          };
          if (!isNewProspect) {
            params.id = prospect.id;
          }

          params[$scope.pelf.prospect.apiFieldNames.pelf_scale] = $scope.editData.scale;
          params[$scope.pelf.prospect.apiFieldNames.pelf_stage] = $scope.editData.stage;

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
            // Update the ID (essential for when we've just created a new prospect).
            prospect.id = result.id;
            prospect.subject = $scope.editData.name;
            prospect.scale = $scope.editData.scale;
            prospect.stage = $scope.editData.stage;
            prospect.details = $scope.editData.details;
            prospect.date = $scope.editData.when;
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
            if (isNewProspect) {
              // Redirect to proper path for this prospect.
              $location.path("/pelf/prospect/" + prospect.id);
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
        };

        if (!prospect.id) {
          // Open new prospects in edit mode.
          $scope.editStart();
        }

      }], // end of controller.
      link: function(scope, elem, attrs) {
        if (true) return;
        if (scope.prospect && !scope.prospect.id) {
          $timeout(function() {
            elem.find('input[name="prospect_name"]').focus();
          });
        }
      },
      templateUrl: '~/pelf/Prospect.html',
    };
  }]);

})(angular, CRM.$, CRM._);
