(function(angular, $, _) {

  // Nb. directive MUST start with lowercase letter.
  angular.module('pelf').directive('pelfOrgsList', function(crmApi, $q) {
    return {
      // The activity is fed in via attribute.
      // The other vars are also bound by attribute name. This means we can
      // expose methods to the parent.
      scope: {
        activity   : '=',
        editStart  : '=',
        editCancel : '=',
        editSave   : '=',
      },
      controller: ['$scope', function ($scope) {
        $scope.crmUrl = CRM.url;
        console.log("orgslist controller", $scope);

        $scope.editData = false;

        $scope.editStart = function () {
          // Take a copy of the values.
          $scope.editData = {v:_.map($scope.activity.contactWith, 'contact_id').join(',')};
          console.log("EDIT START", $scope.editData);
        };

        $scope.editCancel = function () {
          $scope.editData = false;
        };

        $scope.editSave = function () {
          // diff the values, submit changes.
          // For some reason this editData is unchanged, even though the view updates.
          var orig = _.map($scope.activity.contactWith, function(o) {return parseInt(o.contact_id);});
          var newVals = _.map($scope.editData.v.split(','), function(o) {return parseInt(o);});

          var q = $q.when();
          var newContactWith = [];

          var toDelete = _.difference(orig, newVals);
          _.forEach(toDelete, function(contact_id) {
            // Need to know the activity_contact_id for this.
            var contactWith = _.find($scope.activity.contactWith, {contact_id: contact_id.toString() });
            if (contactWith) {
              q.then(function() { return crmApi('ActivityContact', 'delete', { id: contactWith.activity_contact_id }); });
            }
          });
          q.then(function() {
            // Now update our model by removing the old contactWith records.
            _.remove($scope.activity.contactWith, function(cw) {
                return toDelete.indexOf(parseInt(cw.contact_id)) > -1;
              });
          });

          var toAdd = _.difference(newVals, orig);
          _.forEach(toAdd, function(contact_id) {
            q.then(
              function() {
                return crmApi('ActivityContact', 'create', {
                  contact_id: contact_id,
                  activity_id: $scope.activity.id,
                  record_type_id: "Activity Targets",
                  'api.Contact.getsingle': {id: contact_id, return:'id,display_name'},
                  sequential: 1
                })
                .then(function(result) {
                  console.log("add ok", result);
                  $scope.activity.contactWith.push({
                    id: result.values[0]['api.Contact.getsingle'].id,
                    display_name: result.values[0]['api.Contact.getsingle'].display_name,
                    activity_contact_id: result.id
                  });
                });
              }
            );
          });

          q.then(function() {
            $scope.editData = false;
          });

          return q;
        };

        // Start new prospects in edit mode.
        if ($scope.activity.id === null) {
          console.log("Starting edit mode because no activity id");
          $scope.editStart();
        }
        console.log("orgs list controller ends");
      }],
      templateUrl: '~/pelf/OrgsList.html'
    };
  });

})(angular, CRM.$, CRM._);

