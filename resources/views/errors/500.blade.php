@extends('errors.layout')

@section('title', 'Erreur serveur')
@section('code', '500')
@section('label', 'ERREUR INTERNE')
@section('heading', 'Une erreur est survenue')
@section('message', 'Le service est momentanément indisponible. Réessayez dans quelques instants ; si le problème persiste, prévenez l’équipe.')
