import 'package:flutter/material.dart';

import 'master_data_screen.dart';

MasterDataScreen hydrantLocationsScreen() => MasterDataScreen(
      title: 'Hydrant Locations',
      icon: Icons.location_on_outlined,
      idKey: 'id_location',
      loader: (api) => api.getFireHydrantLocations(),
      creater: (api, data) => api.createFireHydrantLocation(data),
      updater: (api, id, data) => api.updateFireHydrantLocation(id, data),
      deleter: (api, id) => api.deleteFireHydrantLocation(id),
      fields: const [
        FieldConfig(key: 'name', label: 'Name'),
      ],
    );

MasterDataScreen incidentTypesScreen() => MasterDataScreen(
      title: 'Incident Types',
      icon: Icons.warning_amber_outlined,
      idKey: 'id',
      loader: (api) => api.getIncidentTypes(),
      creater: (api, data) => api.createIncidentType(data),
      updater: (api, id, data) => api.updateIncidentType(id, data),
      deleter: (api, id) => api.deleteIncidentType(id),
      fields: const [
        FieldConfig(key: 'name', label: 'Name'),
        FieldConfig(
          key: 'level',
          label: 'Level',
          required: false,
          dropdownItems: ['A', 'B', 'C'],
        ),
      ],
    );

MasterDataScreen esEwAreasScreen() => MasterDataScreen(
      title: 'ES&EW Areas',
      icon: Icons.shower_outlined,
      idKey: 'id',
      loader: (api) => api.getEsEwAreas(),
      creater: (api, data) => api.createEsEwArea(data),
      updater: (api, id, data) => api.updateEsEwArea(id, data),
      deleter: (api, id) => api.deleteEsEwArea(id),
      fields: const [
        FieldConfig(key: 'name', label: 'Name'),
      ],
    );

MasterDataScreen usersScreen() => MasterDataScreen(
      title: 'Users',
      icon: Icons.person_outline,
      idKey: 'id',
      loader: (api) => api.getUsers(),
      creater: (api, data) => api.createUser(data),
      updater: (api, id, data) => api.updateUser(id, data),
      deleter: (api, id) => api.deleteUser(id),
      fields: const [
        FieldConfig(key: 'name', label: 'Name'),
        FieldConfig(key: 'username', label: 'Username'),
        FieldConfig(key: 'phone', label: 'Phone', required: false),
        FieldConfig(
          key: 'role',
          label: 'Role',
          dropdownItems: [
            'super_admin',
            'admin',
            'inspector',
            'viewer',
            'supervisor',
            'she_section_head',
            'user_dept_head',
          ],
        ),
      ],
    );
